ALTER TABLE `tipitaka_tags` CHANGE `paliname` `paliname` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NULL DEFAULT NULL; 
ALTER TABLE `tipitaka_collection_items` ADD `notes_bottom` TEXT NULL AFTER `default_view`; 
ALTER TABLE `tipitaka_toc` ADD `urlpart` VARCHAR(20) NULL AFTER `disabletranslalign`, ADD `urlfull` VARCHAR(255) NULL AFTER `urlpart`; 
ALTER TABLE `tipitaka_toc` ADD INDEX `urlfull` (`urlfull`); 
ALTER TABLE `tipitaka_toc` ADD `nextid` INT NULL AFTER `urlfull`, ADD `previd` INT NULL AFTER `nextid`;

ALTER TABLE `tipitaka_comments` ADD `tag` VARCHAR(50) NULL AFTER `authorname`;
ALTER TABLE `tipitaka_collection_items` ADD `tag` VARCHAR(50) NULL AFTER `notes_bottom`;