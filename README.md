# GCWorld Router

The GCWorld Router is intended as a static-only routing system for front controllers.  It comes packaged with the following features:

  - Multi-route management
  - Automatic Session Starting
  - Automated PEX Permissions testing, including access to replacement keys
  - *NEW* lintFile function to assist in resolving routing issues.


### Hooks
 - before_request
 - pre-session_start
 - post-session_start
 - 403
 - 403_pex (not automated)
 - before_handler
 - after_handler
 - 404
 - after_request
 

## Todo

  - Add support for tracking route handling time (microtime before instantiation and after request handling)

### Version
4.3.0

### Additional Information

* [GCWorld on GitHub](https://github.com/KongHack)
