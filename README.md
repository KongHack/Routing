# GCWorld Router

The GCWorld Router is intended as a static-only routing system for front controllers.  It comes packaged with the following features:

  - Multi-route management
  - Automatic Session Starting
  - Automated PEX Permissions testing, including access to replacement keys
  - lintFile function to assist in resolving routing issues.


### Hooks
 - before_request
 - pre-session_start
 - post-session_start
 - 403
 - 403_pex (not automated)
 - before_handler (runs on instantiation)
 - after_handler (runs after instantiation)
 - before_request_method
 - after_request_method
 - 404
 - after_output
 

## Todo

  - Add support for tracking route handling time (microtime before instantiation and after request handling)

### Version
4.4.3
