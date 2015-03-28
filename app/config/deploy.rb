
#### STAGING
 set   :domain,            "54.77.104.228"
 set    :user,              "ubuntu"
 set   :application,       "aiesec"
 set   :deploy_to,         "/home/ubuntu/#{application}"
 ssh_options[:forward_agent] = true
 ssh_options[:keys] = [File.join(ENV["HOME"], "/.ssh/cargo-planning-test.pem")]
######

set :branch do
  default_tag = `git tag`.split("\n").last

  tag = Capistrano::CLI.ui.ask "Tag to deploy (make sure to push the tag first): [#{default_tag}] "
  tag = default_tag if tag.empty?
  tag
end



set   :scm,               :git
set   :repository,        "git@bitbucket.org:stev_ro/smart.git"

role  :web,               domain
role  :app,               domain
role  :db,                domain, :primary => true

set   :use_sudo,          false
set   :keep_releases,     3

set   :deploy_via,        :remote_cache

set   :shared_files,      ["app/config/parameters.yml"]
set   :shared_children,   [app_path + "/logs", web_path + "/uploads", "vendor"]
set   :use_composer,      true
set   :update_vendors,    false

logger.level = Logger::MAX_LEVEL

default_run_options[:pty] = true

after "deploy",           "deploy:set_perms_cache_logs"

namespace :deploy do
  task :set_perms_cache_logs, :roles => :app do
    run "cd /home/ubuntu/#{application}/current && SYMFONY_ENV=prod php app/console cache:clear --env=prod"
    run "cd /home/ubuntu/#{application}/current && SYMFONY_ENV=prod php app/console doctrine:schema:update --force"
    run "sudo chmod -R 777 /home/ubuntu/#{application}/current/app/cache/"
    run "sudo chmod -R 777 /home/ubuntu/#{application}/current/app/logs/"
    run "sudo chmod -R 777 /home/ubuntu/#{application}/current/web/uploads/"
    run "sudo chmod -R 777 /home/ubuntu/#{application}/current/data/documents/"
    run "sudo /usr/sbin/service php5-fpm restart"
  end
end

# https://stevegrunwell.com/blog/restart-php-fpm-during-deployments/
# To set it up on your server, run visudo as root and add the following to it under the “User
# privilege specification” comment:
# deploy ALL=NOPASSWD: /usr/sbin/service php5-fpm restart
