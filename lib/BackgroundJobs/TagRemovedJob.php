<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ContextChat\BackgroundJobs;

use OCA\ContextChat\Service\FsEventService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\IRootFolder;

/**
 * Runs after the "AI knowledge" tag was removed from a file or folder, once the
 * tag rows are consistent again, and removes from the index only what is no
 * longer covered by a tag.
 */
class TagRemovedJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private IRootFolder $rootFolder,
		private FsEventService $fsEventService,
	) {
		parent::__construct($timeFactory);
	}

	/**
	 * @param array{file_id:int} $argument
	 */
	protected function run($argument): void {
		$node = $this->rootFolder->getFirstNodeById($argument['file_id']);
		if ($node === null) {
			return;
		}
		$this->fsEventService->onTagRemoved($node);
	}
}
