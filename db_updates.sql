ALTER TABLE `tipitaka_tags` CHANGE `paliname` `paliname` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NULL DEFAULT NULL; 
ALTER TABLE `tipitaka_collection_items` ADD `notes_bottom` TEXT NULL AFTER `default_view`; 
ALTER TABLE `tipitaka_toc` ADD `urlpart` VARCHAR(20) NULL AFTER `disabletranslalign`, ADD `urlfull` VARCHAR(255) NULL AFTER `urlpart`; 
ALTER TABLE `tipitaka_toc` ADD INDEX `urlfull` (`urlfull`); 
