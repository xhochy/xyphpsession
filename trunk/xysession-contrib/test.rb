#!/usr/bin/env ruby
########################################
# Tasks for testing XhochY PHP Session #
########################################

## Tasks ##

task :xysession_test do |t|
  sh '/usr/bin/env phpunit --log-graphviz xysession-tests-graph/main.dot --report ./xysession-tests-report XYSession_AllTests xysession-tests/all.tests.php'
end