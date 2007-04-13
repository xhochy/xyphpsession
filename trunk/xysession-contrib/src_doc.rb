#!/usr/bin/env ruby
#######################################################
# Tasks for creating the PHP-sourcecode documentation # 
#######################################################

## Includes ##

require 'rake/clean';

## Tasks ##

task :src_doc => 'xysession-docs/elementindex.html'

## File Tasks ##

file 'xysession-docs/elementindex.html' => FileList['xysession-includes/*.php', 'xyexception-includes/*.php'] do
  sh 'phpdoc -t xysession-docs/ -f ' + FileList['xyexception-includes/*.php', 'xysession-includes/*.php'].join(',')
end

## clean Task ##

CLEAN.include('xysession-docs/*')
CLEAN.exclude('xysession-docs/.svn')