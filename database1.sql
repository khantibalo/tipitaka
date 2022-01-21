CREATE TABLE `tipitaka_statistics` (
  `statid` int NOT NULL,
  `accessdate` date NOT NULL,
  `accesscount` int NOT NULL,
  `nodeid` int DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

ALTER TABLE `tipitaka_statistics`
  ADD PRIMARY KEY (`statid`);

ALTER TABLE `tipitaka_statistics`
  MODIFY `statid` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
  
  ALTER TABLE `tipitaka_toc` ADD `disabletranslalign` BOOLEAN NOT NULL DEFAULT FALSE AFTER `hasprologue`; 
  
