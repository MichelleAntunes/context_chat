<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ContextChat\Listener;

use OCA\ContextChat\BackgroundJobs\TagRemovedJob;
use OCA\ContextChat\Service\FsEventService;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\IRootFolder;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAssignedEvent;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagUnassignedEvent;

/**
 * @template-implements IEventListener<Event>
 */
class TagListener implements IEventListener {
	public function __construct(
		private ISystemTagManager $tagManager,
		private IRootFolder $rootFolder,
		private FsEventService $fsEventService,
		private IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof TagAssignedEvent) && !($event instanceof TagUnassignedEvent)) {
			return;
		}

		try {
			$tag = $this->tagManager->getTag('AI knowledge', true, true);
		} catch (TagNotFoundException $e) {
			return;
		}

		if (!in_array((int)$tag->getId(), $event->getTags(), true)) {
			return;
		}

		foreach ($event->getObjectIds() as $objectId) {
			if ($event instanceof TagUnassignedEvent) {
				// The tag mapper emits this event while it is still rewriting the tag
				// rows, so a tag on a parent folder cannot be seen yet. Decide later.
				$this->jobList->add(TagRemovedJob::class, ['file_id' => (int)$objectId]);
				continue;
			}

			$node = $this->rootFolder->getFirstNodeById((int)$objectId);
			if ($node === null) {
				continue;
			}
			$this->fsEventService->onInsert($node);
		}
	}
}
