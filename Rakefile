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

desc 'run automated tests'
task :tests do
  cmd = "phpunit -c app/"
  puts `#{cmd}`
end

task :default => :phpcs
