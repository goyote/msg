# 1.0 (02/16/11)

- Added `MSG::CRITICAL`
- Changed public `MSG::$_instances` to protected
- Added `MSG::$default_view
- Updated DocBlocks, inline comments and code snippets
- Refactored switch statement (for different storage mediums) into their own driver file
- Removed roar files and example (migrated to asset module)
- Removed MSG_Exception (replaced with ErrorException)
- Added CHANGELOG
- Switched to MIT license
- MSG is abstract
- Changed ul.id to ul.class for re-use