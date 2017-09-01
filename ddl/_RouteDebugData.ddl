CREATE TABLE `_RouteDebugData`
(
  route_id INT NOT NULL AUTO_INCREMENT,
  route_path VARCHAR(250) NOT NULL,
  route_name VARCHAR(250) NOT NULL,
  route_title VARCHAR(250) NOT NULL,
  route_session TINYINT DEFAULT 0 NOT NULL,
  route_autoWrapper TINYINT DEFAULT 0 NOT NULL,
  route_hits INT DEFAULT 0 NOT NULL,
  route_class LONGTEXT NOT NULL,
  route_pre_args LONGTEXT NOT NULL,
  route_post_args LONGTEXT NOT NULL,
  route_pexCheck LONGTEXT NOT NULL,
  route_pexCheckAny LONGTEXT NOT NULL,
  route_pexCheckExact LONGTEXT NOT NULL,
  route_meta LONGTEXT NOT NULL
);
