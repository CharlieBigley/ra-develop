-- Register com_ra_develop component in the extensions table
-- Run this SQL directly in phpMyAdmin or via command line if the component cannot be discovered/installed via symlink

INSERT INTO `dev_extensions` (
  `name`,
  `type`,
  `element`,
  `folder`,
  `client_id`,
  `enabled`,
  `access`,
  `protected`,
  `manifest_cache`,
  `params`,
  `custom_data`
) VALUES (
  'RA Develop',
  'component',
  'com_ra_develop',
  '',
  1,
  1,
  1,
  0,
  '{"name":"RA Develop","type":"component","creationDate":"2026-02-02","author":"Charlie Bigley","copyright":"2026 Charlie Bigley","authorEmail":"charlie@bigley.me.uk","license":"GNU General Public License version 2 or later; see LICENSE.txt","version":"1.0.1","description":"Tools to assist development process","element":"com_ra_develop"}',
  '{}',
  ''
)
ON DUPLICATE KEY UPDATE
  `name` = 'RA Develop',
  `enabled` = 1,
  `manifest_cache` = '{"name":"RA Develop","type":"component","creationDate":"2026-02-02","author":"Charlie Bigley","copyright":"2026 Charlie Bigley","authorEmail":"charlie@bigley.me.uk","license":"GNU General Public License version 2 or later; see LICENSE.txt","version":"1.0.1","description":"Tools to assist development process","element":"com_ra_develop"}';
