desc 'run a server'
task :serve do
  console "server:run"
end

desc 'run a psysh console'
task :psysh do
  system "vendor/psy/psysh/bin/psysh"
end

desc 'checks coding standards'
task :phpcs do
  exit 1 unless system "bin/phpcs --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ --extensions=php src/"
end

desc 'attempts to automatically fix coding standards'
task :phpcbf do
  system "bin/phpcbf --standard=vendor/leaphub/phpcs-symfony2-standard/leaphub/phpcs/Symfony2/ --extensions=php src/"
end

desc 'Attempt a trade on bitstamp, from Heroku'
task :"heroku-trade-bitstamp" do
  system "heroku run 'php app/console trade:bitstamp'"
end

desc 'Take a snapshot of bitstamp data, from Heroku'
task :"heroku-snapshot-bitstamp" do
  system "heroku run 'php app/console snapshot:bitstamp'"
end

def dosystem(base, *args)
  cmd = args.unshift(base).join(" ")
  exit 1 unless system cmd
end

def console(*args)
  base = "app/console"
  dosystem base, args
end

def phpunit(*args)
  base = "bin/phpunit -c app/"
  dosystem base, args
end

def blackfire(*args)
  base = "blackfire run php bin/phpunit -c app/"
  dosystem base, args
end

namespace :blackfire do
  desc 'profile all phpunit tests'
  task :all do
    blackfire
  end

  desc 'profile all phpunit tests not tagged with slow'
  task :fast do
    blackfire "--exclude-group", "slow"
  end
end

namespace :phpunit do
  desc 'run all phpunit tests'
  task :all do
    phpunit
  end

  desc 'run unstable phpunit tests only'
  task :unstable do
    phpunit "--exclude-group", "stable", "--debug"
  end

  desc 'get the unstable coverage report'
  task :"unstable-coverage" do
    phpunit "--exclude-group", "stable", "--debug", "--coverage-html", "coverage"
  end

  desc 'run all phpunit tests not tagged with slow'
  task :fast do
    phpunit "--exclude-group", "slow"
  end

  desc 'run all phpunit tests with an HTML coverage report'
  task :coverage do
    phpunit "--coverage-html", "coverage"
  end

  desc 'run all phpunit tests in a travis compatible way'
  task :travis do
    # Do a fast run to flush out failing tests before generating the full
    # coverage report. #failfast ;)
    phpunit "--exclude-group", "slow", "--exclude-group", "requiresAPIKey"
  end

  desc 'run all phpunit tests and generage clover coverage'
  task :clover do
    phpunit "--coverage-clover", "build/logs/clover.xml", "--exclude-group", "requiresAPIKey"
  end
end

namespace :security do
  desc 'Check for known composer package security vulnerabilities'
  task :check do
    console "security:check"
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
task :travis => [:phpcs, :"phpunit:travis", :"security:check"]

task :default => :help

# No description for this task so it doesn't show in task lists.
task :help do
  puts 'Use rake -D for detailed information on available tasks.'
  puts

  system 'rake', '-T'
end
