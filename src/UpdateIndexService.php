<?php

namespace PhpLocate;

use PhpLocate\Builder\TypeToNodeService;
use PhpLocate\Internal\ChangeSetProjection\ChangedFile;
use PhpLocate\Internal\ChangeSetProjection\NewFile;
use PhpLocate\Internal\ChangeSetProjection\RemovedFile;
use PhpLocate\Internal\ChangeSetProjectionService;
use PhpLocate\Internal\FileInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\SplFileInfo;

class UpdateIndexService {
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
		
		$index->saveTo($indexPath);
	}
}