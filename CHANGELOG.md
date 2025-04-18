# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased](https://github.com/KongHack/Routing)



## [5.4.4](https://github.com/KongHack/Routing/releases/tag/5.4.4)
- @GameCharmer Limit the route attribute to classes, add IS_REPEATABLE



## [5.4.3](https://github.com/KongHack/Routing/releases/tag/5.4.3)
- @GameCharmer Annotation System!



## [5.4.2](https://github.com/KongHack/Routing/releases/tag/5.4.2)
- @GameCharmer PHP 8.4



## [5.4.1](https://github.com/KongHack/Routing/releases/tag/5.4.1)
- @GameCharmer Update Interfaces



## [5.4.0](https://github.com/KongHack/Routing/releases/tag/5.4.0)
- @GameCharmer Remove PHPEncoder, PHP 8.4



## [5.3.7](https://github.com/KongHack/Routing/releases/tag/5.3.7)
- @GameCharmer Update Composer Dependencies



## [5.3.6](https://github.com/KongHack/Routing/releases/tag/5.3.6)
- @GameCharmer Update redirect default so GET is 301 permanent, but anything else (POST/PUT/DELETE/ETC) are 302 while still allowing overrides



## [5.3.5](https://github.com/KongHack/Routing/releases/tag/5.3.5)
- @GameCharmer Default redirects to 301 permanent redirect and allow changing via exception code



## [5.3.4](https://github.com/KongHack/Routing/releases/tag/5.3.4)
- @GameCharmer Update Twig per CVE-2024-45411



## [5.3.3](https://github.com/KongHack/Routing/releases/tag/5.3.3)
- @GameCharmer Eliminate call_user_func/_array entirely



## [5.3.2](https://github.com/KongHack/Routing/releases/tag/5.3.2)
- @GameCharmer Update exceptions to better fall in line with call user func array



## [5.3.1](https://github.com/KongHack/Routing/releases/tag/5.3.1)
- @GameCharmer replace is_array with not empty to ensure we're passing a proper array



## [5.3.0](https://github.com/KongHack/Routing/releases/tag/5.3.0)
- @GameCharmer Adjust the way Hook calls user functions


## [5.2.5](https://github.com/KongHack/Routing/releases/tag/5.2.5)
- @GameCharmer Update composer/composer



## [5.2.4](https://github.com/KongHack/Routing/releases/tag/5.2.4)
- @GameCharmer Update composer dependencies (Dependabot)



## [5.2.3](https://github.com/KongHack/Routing/releases/tag/5.2.3)
- @GameCharmer Improve message of `ReverseRouteNotFoundException`



## [5.2.2](https://github.com/KongHack/Routing/releases/tag/5.2.2)
- @GameCharmer Update return on `CoreRouter::getInstance()` to return `RoutingInterface` instead of `static`



## [5.2.1](https://github.com/KongHack/Routing/releases/tag/5.2.1)
- @GameCharmer Finish implementing routing interface



## [5.2.0](https://github.com/KongHack/Routing/releases/tag/5.2.0)
- @GameCharmer Implement Routing Interface



## [5.1.2](https://github.com/KongHack/Routing/releases/tag/5.1.2)
- @GameCharmer `purgeRedis` method for Route Discovery
- @GameCharmer Add default name to Route Discovery constructor



## [5.1.1](https://github.com/KongHack/Routing/releases/tag/5.1.1)
- @GameCharmer `RouteClassNotFoundException` exception



## [5.1.0](https://github.com/KongHack/Routing/releases/tag/5.1.0)
- @GameCharmer !!!Legacy Router Removed!!!



## [5.0.2](https://github.com/KongHack/Routing/releases/tag/5.0.2)
- @GameCharmer Patch Exception Hooks



## [5.0.1](https://github.com/KongHack/Routing/releases/tag/5.0.1)
- @GameCharmer Add deprecated tag to original Router.
  - Original Router will still function as a stub that calls the default instance of CoreRouter



## [5.0.0](https://github.com/KongHack/Routing/releases/tag/5.0.0)
- @GameCharmer Router Rewrite
  - Runs on an instance array
  - Loader no longer uses statics
  - Routing tables are inside of instance name now, so you can have more than 1 router!
  - Note: This is still undergoing extensive testing!



## [4.7.3](https://github.com/KongHack/Routing/releases/tag/4.7.3)
- @GameCharmer Ensure session is started before running security checks!



## [4.7.2](https://github.com/KongHack/Routing/releases/tag/4.7.2)
- @GameCharmer Adjust DocBlock



## [4.7.1](https://github.com/KongHack/Routing/releases/tag/4.7.1)
- @GameCharmer Update deprecated public properties to non-deprecated protected properties
- @GameCharmer More type hints, more `exception`s instead of `return false`s



## [4.7.0](https://github.com/KongHack/Routing/releases/tag/4.7.0)
- @GameCharmer Implement type hints, throw exception on reverse route not found



## [4.6.0](https://github.com/KongHack/Routing/releases/tag/4.6.0)
- @GameCharmer Updated Reflection DocBlock
- @GameCharmer Bumped PHP min version to 8.0 


## [4.5.5](https://github.com/KongHack/Routing/releases/tag/4.5.5)
- @GameCharmer Allow disabling PHP Lint process



## [4.5.4](https://github.com/KongHack/Routing/releases/tag/4.5.4)
- @GameCharmer Base64 Token



## [4.5.3](https://github.com/KongHack/Routing/releases/tag/4.5.3)
- @GameCharmer Update Composer Dependencies



## [4.5.2](https://github.com/KongHack/Routing/releases/tag/4.5.2)
- @GameCharmer Fix Utilities Usage



## [4.5.1](https://github.com/KongHack/Routing/releases/tag/4.5.1)
- @GameCharmer Update Composer Dependencies



## [4.5.0](https://github.com/KongHack/Routing/releases/tag/4.5.0)
- @GameCharmer Update PHPDocumentor ReflectionDocBlock



## [4.4.9](https://github.com/KongHack/Routing/releases/tag/4.4.9)
- @GameCharmer add support for throwing router exceptions within hooks



## [4.4.8](https://github.com/KongHack/Routing/releases/tag/4.4.8)
- @GameCharmer Add getPath methods to avoid things like `reverseMe(getFoundRouteArgs())`



## [4.4.7](https://github.com/KongHack/Routing/releases/tag/4.4.7)
- @GameCharmer Officially deprecate usage of the public static variables



## [4.4.6](https://github.com/KongHack/Routing/releases/tag/4.4.6)
- @GameCharmer Pass code through in router exception custom, adjust default code to 400



## [4.4.5](https://github.com/KongHack/Routing/releases/tag/4.4.5)
- @GameCharmer Fix missing static functions



## [4.4.4](https://github.com/KongHack/Routing/releases/tag/4.4.4)
- @GameCharmer fix infinite recursion



## [4.4.3](https://github.com/KongHack/Routing/releases/tag/4.4.3)
- @GameCharmer return instead of die on exception execute



## [4.4.2](https://github.com/KongHack/Routing/releases/tag/4.4.2)
- @GameCharmer Add Calling Method method



## [4.4.1](https://github.com/KongHack/Routing/releases/tag/4.4.1)
- @GameCharmer Remove all compacts for PHP 7.3



## [4.4.0](https://github.com/KongHack/Routing/releases/tag/4.4.0)
- @GameCharmer Migrated interfaces from old interfaces package to this package
- New Interface for JSON only handling and accompanying abstract
- Moved existing interfaces into an interfaces sub-namespace
- gcworld/interfaces updated from 3.2.0 to 3.3.1  
  See changes: https://github.com/KongHack/Interfaces/compare/3.2.0...3.3.1  
  Release notes: https://github.com/KongHack/Interfaces/releases/tag/3.3.1


## [4.3.3](https://github.com/KongHack/Routing/releases/tag/4.3.3)
- @GameCharmer Add UUID Token



## [4.3.2](https://github.com/KongHack/Routing/releases/tag/4.3.2)
- @GameCharmer Updates hooks, add before and after hooks for request method execution



## [4.3.1](https://github.com/KongHack/Routing/releases/tag/4.3.1)
- @GameCharmer Allow node being passed into router exception pex 403 to be either a string or an array



## [4.3.0](https://github.com/KongHack/Routing/releases/tag/4.3.0)
- @GameCharmer composer update
- @GameCharmer *New* RouterExceptionPEX403 for throwing custom PEX messages
- gcworld/interfaces updated from 3.1.3 to 3.2.0
  See changes: https://github.com/KongHack/Interfaces/compare/3.1.3...3.2.0
  Release notes: https://github.com/KongHack/Interfaces/releases/tag/3.2.0



## [4.2.1](https://github.com/KongHack/Routing/releases/tag/4.2.1)
- @GameCharmer add lintFile function to LoadRoutes class
 

## [4.2.0](https://github.com/KongHack/Routing/releases/tag/4.2.0)
- @GameCharmer Resolve remaining issues with DocBlocks


## [4.1.10](https://github.com/KongHack/Routing/releases/tag/4.1.10)
- @GameCharmer Update License Entry


## [4.1.9](https://github.com/KongHack/Routing/releases/tag/4.1.9)
- @GameCharmer Update DocBlock Library


## [4.1.8](https://github.com/KongHack/Routing/releases/tag/4.1.8)
- @GameCharmer add more debugging to load routes


## [4.1.7](https://github.com/KongHack/Routing/releases/tag/4.1.7)
- @GameCharmer add more debugging to the processor


## [4.1.6](https://github.com/KongHack/Routing/releases/tag/4.1.6)
- @GameCharmer Add support for the http status code as the exception code number


## [4.1.5.3](https://github.com/KongHack/Routing/releases/tag/4.1.5.3)
- @GameCharmer Tweaks to prevent issues


## [4.1.5.2](https://github.com/KongHack/Routing/releases/tag/4.1.5.2)
- @GameCharmer Ugh, forgot to rename variables


## [4.1.5.1](https://github.com/KongHack/Routing/releases/tag/4.1.5.1)
- @GameCharmer fix syntax checking


## [4.1.5](https://github.com/KongHack/Routing/releases/tag/4.1.5)
- @GameCharmer Add syntax checking support


## [4.1.4](https://github.com/KongHack/Routing/releases/tag/4.1.4)
- @GameCharmer Replace DIEs with throw new Exception
- @GameCharmer broaden the scope of exception catching to include all handler interaction


## [4.1.3](https://github.com/KongHack/Routing/releases/tag/4.1.3)
- @GameCharmer Better exception handling


## [4.1.2](https://github.com/KongHack/Routing/releases/tag/4.1.2)
- @GameCharmer Advanced Handler


## [4.1.1](https://github.com/KongHack/Routing/releases/tag/4.1.1)
- @GameCharmer Added title to DDL, DB, and code


## [4.1.0](https://github.com/KongHack/Routing/releases/tag/4.1.0)
- @GameCharmer Migrated change log to GitHub
- @GameCharmer Added custom exceptions


## [4.0.1](https://github.com/KongHack/Routing/releases/tag/4.0.1)
- @GameCharmer Added pre/post session_start hooks ``pre-session_start`` AND ``post-session_start``
- WARNING: Removed routes from the compact in several hook calls, since that's a tad overkill as you can request them when needed
 
 
## [4.0.0](https://github.com/KongHack/Routing/releases/tag/4.0.0)
- @GameCharmer fully PSR2 compliant, ran through PHPCS/CFB, converted _xhr to XHR
- WARNING: This is a breaking change!


## [3.7.5.1](https://github.com/KongHack/Routing/releases/tag/3.7.5.1)
- @GameCharmer remove the meta key from the router, as data contains everything


## [3.7.5](https://github.com/KongHack/Routing/releases/tag/3.7.5)
- @GameCharmer updated Meta to handle key/value pairs in separate lines
- @GameCharmer added some more public statics to the router for data/meta access


## [3.7.4](https://github.com/KongHack/Routing/releases/tag/3.7.4)
- @GameCharmer added a reverseMe function that will use the current foundRouteNameClean


## [3.7.3.2](https://github.com/KongHack/Routing/releases/tag/3.7.3.2)
- @GameCharmer Force array on pre/post args


## [3.7.3.1](https://github.com/KongHack/Routing/releases/tag/3.7.3.1)
- @GameCharmer Run a truncate table on the raw route table to prevent stale routes from being listed


## [3.7.3](https://github.com/KongHack/Routing/releases/tag/3.7.3)
- @GameCharmer Added support for complex routes per file


## [3.7.2](https://github.com/KongHack/Routing/releases/tag/3.7.2)
- @GameCharmer Upgraded Debugger, added new Raw Route table (nearly identical to debugger) for external manipulation


## [3.7.1.2](https://github.com/KongHack/Routing/releases/tag/3.7.1.2)
- @GameCharmer resolve legacy issues


## [3.7.1.1](https://github.com/KongHack/Routing/releases/tag/3.7.1.1)
- @GameCharmer followup fix


## [3.7.1](https://github.com/KongHack/Routing/releases/tag/3.7.1)
- @GameCharmer bring over meta adjustment from other repo


## [3.7.0](https://github.com/KongHack/Routing/releases/tag/3.7.0)
- @GameCharmer Added support for "autoWrapper" per new interface
- @GameCharmer Optimized code


## [3.6.3](https://github.com/KongHack/Routing/releases/tag/3.6.3)
- @GameCharmer Composer Update


## [3.6.2.1](https://github.com/KongHack/Routing/releases/tag/3.6.2.1)
- Emergency bug fix for that thing that didn't get quite completely reverted


## [3.6.2](https://github.com/KongHack/Routing/releases/tag/3.6.2)
- The real culprit was REDIS!  BWA HA HA HA HA!


## [3.6.1](https://github.com/KongHack/Routing/releases/tag/3.6.1)
- REVERTED THAT LAST THING


## [3.6.0](https://github.com/KongHack/Routing/releases/tag/3.6.0)
- Fixed @GameCharmer cleanup routine to empty out old / garbage route files


## [3.5.3](https://github.com/KongHack/Routing/releases/tag/3.5.3)
- Fixed @GameCharmer weird ltrim bug


## [3.5.2](https://github.com/KongHack/Routing/releases/tag/3.5.2)
- Fixed @GameCharmer attachRedis is now functional
- Added @GameCharmer Route Prefix system


## [3.5.1](https://github.com/KongHack/Routing/releases/tag/3.5.1)
- Fixed @GameCharmer Possible pex check fix


## [3.5.0](https://github.com/KongHack/Routing/releases/tag/3.5.0)
- Fixed @GameCharmer Massive issue with permissions

