<?php

namespace PhpLocate;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PhpLocate\Builder\TypeToNodeService;
use PhpLocate\Internal\ChangeSetProjection\ChangedFile;
use PhpLocate\Internal\ChangeSetProjection\NewFile;
use PhpLocate\Internal\ChangeSetProjection\RemovedFile;
use PhpLocate\Internal\ChangeSetProjectionService;
use PhpLocate\Internal\FileInfo;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class UpdateIndexService {
	public const INDEX_VERSION = '20260114';

	public function __construct(
		private readonly LoggerInterface $logger
	) {}
	
	/**
	 * @param string $indexPath
	 * @param iterable<FileInfo|SplFileInfo> $files
	 * @return void
	 */
	public function updateIndex(string $indexPath, iterable $files): void {
		$index = Index::fromFile($indexPath);
		$rootNode = $index->getFirstNode('/files');

		$existingVersion = $rootNode->getAttr('version', '');
		if($existingVersion !== self::INDEX_VERSION) {
			$this->logger->info(sprintf(
				"Index version mismatch (%s != %s); reindexing all files",
				$existingVersion === '' ? '(none)' : $existingVersion,
				self::INDEX_VERSION
			));
			$rootNode->clear();
		}
		
		$service = new ChangeSetProjectionService();
		$changes = $service->findChanged(items: $files, index: $index);
		
		$discoveryService = new PHPDiscoveryService(new TypeToNodeService());
		
		foreach($changes as $change) {
			if($change instanceof NewFile) {
				$this->logger->info(sprintf("New file discovered: %s at %s", $change->relativePath, date('c', $change->mtime)));
				$node = $index->addFile(
					relativePath: $change->relativePath,
					mtime: $change->mtime,
					hash: $change->hash
				);
				$discoveryService->discoverInFile($change->absolutePath, $node);
			} elseif($change instanceof RemovedFile) {
				$this->logger->info(sprintf("File removed from index: %s", $change->relativePath));
				$index->removeFile(relativePath: $change->relativePath);
			} elseif($change instanceof ChangedFile) {
				$this->logger->info(sprintf("Indexed file changed: %s at %s", $change->relativePath, date('c', $change->mtime)));
				$node = $index->addFile(
					relativePath: $change->relativePath,
					mtime: $change->mtime,
					hash: $change->hash
				);
				$discoveryService->discoverInFile($change->absolutePath, $node);
			}
		}

		$this->mergeTraitUses($index);

		$rootNode->setAttr('version', self::INDEX_VERSION);
		
		$index->saveTo($indexPath);
	}

	private function mergeTraitUses(Index $index): void {
		$doc = $index->getFirstNode('/files')->getDocument();
		$xpath = new DOMXPath($doc);

		$this->removePreviouslyMergedTraitMembers($xpath);

		/** @var array<string, DOMElement> $traitsByName */
		$traitsByName = [];

		$traitNodes = $xpath->query('//trait[@name]');
		if($traitNodes !== false) {
			/** @var DOMElement $trait */
			foreach($traitNodes as $trait) {
				$name = $trait->getAttribute('name');
				if($name !== '') {
					$traitsByName[$name] = $trait;
				}
			}
		}

		$classNodes = $xpath->query('//class');
		if($classNodes === false) {
			return;
		}

		/** @var DOMElement $class */
		foreach($classNodes as $class) {
			$useNodes = $xpath->query('./use[@name]', $class);
			if($useNodes === false) {
				continue;
			}

			/** @var DOMElement $use */
			foreach($useNodes as $use) {
				$traitName = $use->getAttribute('name');
				if($traitName === '' || !isset($traitsByName[$traitName])) {
					continue;
				}

				$visited = [];
				$members = $this->collectTraitMembers($xpath, $traitsByName, $traitName, $visited);

				foreach($members['method'] as $method) {
					$this->appendTraitMemberIfMissing($xpath, $doc, $class, $method, $traitName);
				}

				foreach($members['property'] as $property) {
					$this->appendTraitMemberIfMissing($xpath, $doc, $class, $property, $traitName);
				}
			}
		}
	}

	private function removePreviouslyMergedTraitMembers(DOMXPath $xpath): void {
		$mergedNodes = $xpath->query('//class/*[@fromTrait]');
		if($mergedNodes === false) {
			return;
		}

		/** @var DOMElement $node */
		foreach($mergedNodes as $node) {
			$node->parentNode?->removeChild($node);
		}
	}

	/**
	 * @param array<string, DOMElement> $traitsByName
	 * @param array<string, true> $visited
	 * @return array{method: DOMElement[], property: DOMElement[]}
	 */
	private function collectTraitMembers(DOMXPath $xpath, array $traitsByName, string $traitName, array &$visited): array {
		if(isset($visited[$traitName])) {
			return ['method' => [], 'property' => []];
		}
		$visited[$traitName] = true;

		$trait = $traitsByName[$traitName] ?? null;
		if($trait === null) {
			return ['method' => [], 'property' => []];
		}

		$methods = [];
		$properties = [];

		$methodNodes = $xpath->query('./method[@name]', $trait);
		if($methodNodes !== false) {
			/** @var DOMElement $method */
			foreach($methodNodes as $method) {
				$methods[] = $method;
			}
		}

		$propertyNodes = $xpath->query('./property[@name]', $trait);
		if($propertyNodes !== false) {
			/** @var DOMElement $property */
			foreach($propertyNodes as $property) {
				$properties[] = $property;
			}
		}

		$useNodes = $xpath->query('./use[@name]', $trait);
		if($useNodes !== false) {
			/** @var DOMElement $use */
			foreach($useNodes as $use) {
				$usedTraitName = $use->getAttribute('name');
				if($usedTraitName === '' || !isset($traitsByName[$usedTraitName])) {
					continue;
				}

				$nested = $this->collectTraitMembers($xpath, $traitsByName, $usedTraitName, $visited);
				foreach($nested['method'] as $method) {
					$methods[] = $method;
				}
				foreach($nested['property'] as $property) {
					$properties[] = $property;
				}
			}
		}

		return ['method' => $methods, 'property' => $properties];
	}

	private function appendTraitMemberIfMissing(DOMXPath $xpath, DOMDocument $doc, DOMElement $class, DOMElement $member, string $traitName): void {
		$memberName = $member->getAttribute('name');
		if($memberName === '') {
			return;
		}

		$query = sprintf('./%s[@name=%s]', $member->tagName, $this->xpathLiteral($memberName));
		$nodeList = $xpath->query($query, $class);
		if($nodeList === false) {
			throw new RuntimeException("Invalid XPath query: $query");
		}
		$alreadyPresent = $nodeList->length > 0;
		if($alreadyPresent) {
			return;
		}

		$clone = $doc->importNode($member, true);
		if($clone instanceof DOMElement) {
			$clone->setAttribute('fromTrait', $traitName);
		}
		$class->appendChild($clone);
	}

	private function xpathLiteral(string $value): string {
		if(!str_contains($value, "'")) {
			return "'" . $value . "'";
		}
		if(!str_contains($value, '"')) {
			return '"' . $value . '"';
		}

		$parts = explode("'", $value);
		$escapedParts = array_map(static fn(string $part) => "'" . $part . "'", $parts);
		return "concat(" . implode(", \"'\", ", $escapedParts) . ")";
	}
}
