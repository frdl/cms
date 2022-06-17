# cms
This is a simple Content-Management-System, originally forked from/inspired by [Handsdown CMS](https://github.com/rosell-dk/handsdown).

## WTF - Why the fork?
The main goals was to *keep it simple*, thanks to [rosell-dk](https://github.com/rosell-dk). 
I further added..

### Changes and goals
* rewritten to OOP, CMS class [x]
* removed Parsedown, using league/common-mark instead [x]
* make it more configurable [x]
* make it modular extensible [ ]
* admin and config panels [ ]
* disable the execution of *php* files by default, make it optionally be enabled [x]
* recognize *htm*, *html* files [x]
* changed *include* to *file_get_contents* to prevent the execution of bad code, parse the string instead [x]
* multisite/multi-domain hosting, many instances multitenancy system
* .md, WYSIWYG- , Homepage-, Theme-, ... Editors. [ ]
