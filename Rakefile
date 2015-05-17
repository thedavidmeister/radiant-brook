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

desc 'run phpunit tests'
task :phpunit do
  cmd = "bin/phpunit -c app/"
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

desc 'run all tests'
task :tests => [:phpcs, :phpunit]

task :default => :phpcs
