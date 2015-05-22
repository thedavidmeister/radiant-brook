desc 'checks coding standards'
task :phpcs do
  cmd = "bin/phpcs --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ --extensions=php src/"
  puts `#{cmd}`
end

desc 'attempts to automatically fix coding standards'
task :phpcbf do
  cmd = "bin/phpcbf --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ --extensions=php src/"
  puts `#{cmd}`
end

desc 'run phpunit tests including functional tests'
task :phpunit do
  cmd = "bin/phpunit -c app/"
  puts `#{cmd}`
end

desc 'run phpunit unit tests and create coverage report'
task :"phpunit-coverage" do
  cmd = "bin/phpunit -c app/ --exclude-group functional --coverage-html coverage"
  puts `#{cmd}`
end

desc 'run phpunit tests excluding stable tests'
task :"phpunit-nostable" do
  cmd = "bin/phpunit -c app/ --exclude-group stable"
  puts `#{cmd}`
end

desc 'run phpunit tests excluding livedata tests'
task :"phpunit-noslow" do
  cmd = "bin/phpunit -c app/ --exclude-group slow"
  puts `#{cmd}`
end

desc 'Attempt a trade on bitstamp, from Heroku'
task :"heroku-trade-bitstamp" do
  cmd = "heroku run 'php app/console trade:bitstamp'"
  puts `#{cmd}`
end

desc 'Take a snapshot of bitstamp data, from Heroku'
task :"heroku-snapshot-bitstamp" do
  cmd = "heroku run 'php app/console snapshot:bitstamp'"
  puts `#{cmd}`
end

desc 'run all tests'
task :tests => [:phpcs, :phpunit]

task :default => :phpcs
