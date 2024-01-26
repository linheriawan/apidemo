#SQL
CREATE TABLE `attendees` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `meetid` INT NULL,
  `personid` INT NULL,
  CONSTRAINT `PRIMARY` PRIMARY KEY (`id`)
);