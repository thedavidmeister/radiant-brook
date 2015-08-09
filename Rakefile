desc 'run a server'
task :serve do
  cmd = "app/console server:run"
  exec cmd
end

desc 'run a psysh console'
task :psysh do
  cmd = "vendor/psy/psysh/bin/psysh"
  # puts will not work here.
  exec cmd
end

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

desc 'run security checker'
task :"security-check" do
  cmd = "app/console security:check"
  puts `#{cmd}`
end

desc 'run phpunit tests for travis'
task :"phpunit-travis" do
  cmd = "bin/phpunit -c app/ --coverage-clover build/logs/clover.xml"
  puts `#{cmd}`
end

desc 'run phpunit tests including functional tests'
task :phpunit do
  cmd = "bin/phpunit -c app/"
  puts `#{cmd}`
end

desc 'run phpunit unit tests and create coverage report'
task :"phpunit-coverage" do
  cmd = "bin/phpunit -c app/ --coverage-html coverage"
  puts `#{cmd}`
end

desc 'run phpunit tests excluding stable tests'
task :"phpunit-nostable" do
  cmd = "bin/phpunit -c app/ --exclude-group stable --debug"
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

namespace :git do
  desc 'Cleanup (delete) all local branches that have already been merged into master (locally)'
  task :cleanup do
    `git branch --merged master`.lines.map(&:chomp).select {|i| i != '  master' && i[0,2] != '* ' }.each {|b| sh "git branch -d #{b.strip}" }
  end
end

desc 'run all tests'
task :tests => [:phpcs, :phpunit, :"security-check"]

desc 'run all travis tests'
task :travis => [:phpcs, :"phpunit-travis", :"security-check"]

task :default => :help

# No description for this task so it doesn't show in task lists.
task :help do
  puts 'Use rake -D for detailed information on available tasks.'
  puts

  system 'rake', '-T'
end
