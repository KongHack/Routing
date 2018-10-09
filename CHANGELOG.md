# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased](https://github.com/KongHack/Routing)




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

