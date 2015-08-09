desc 'run a server'
task :serve do
  system "app/console server:run"
end

desc 'run a psysh console'
task :psysh do
  system "vendor/psy/psysh/bin/psysh"
end

desc 'checks coding standards'
task :phpcs do
  system "bin/phpcs --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ --extensions=php src/"
end

desc 'attempts to automatically fix coding standards'
task :phpcbf do
  system "bin/phpcbf --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ --extensions=php src/"
end

desc 'run phpunit tests for travis'
task :"phpunit-travis" do
  system "bin/phpunit -c app/ --coverage-clover build/logs/clover.xml"
end

desc 'run phpunit tests including functional tests'
task :phpunit do
  system "bin/phpunit -c app/"
end

desc 'run phpunit unit tests and create coverage report'
task :"phpunit-coverage" do
  system "bin/phpunit -c app/ --coverage-html coverage"
end

desc 'run phpunit tests excluding stable tests'
task :"phpunit-nostable" do
  system "bin/phpunit -c app/ --exclude-group stable --debug"
end

desc 'run phpunit tests excluding livedata tests'
task :"phpunit-noslow" do
  system "bin/phpunit -c app/ --exclude-group slow"
end

desc 'Attempt a trade on bitstamp, from Heroku'
task :"heroku-trade-bitstamp" do
  system "heroku run 'php app/console trade:bitstamp'"
end

desc 'Take a snapshot of bitstamp data, from Heroku'
task :"heroku-snapshot-bitstamp" do
  system "heroku run 'php app/console snapshot:bitstamp'"
end

namespace :security do
  desc 'Check for known composer package security vulnerabilities'
  task :check do
    system "app/console security:check"
  end
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
task :travis => [:phpcs, :"security:check", :"phpunit-travis"]

task :default => :help

# No description for this task so it doesn't show in task lists.
task :help do
  puts 'Use rake -D for detailed information on available tasks.'
  puts

  system 'rake', '-T'
end
