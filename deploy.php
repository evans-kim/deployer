<?php
namespace Deployer;

use Deployer\Exception\Exception;
use Symfony\Component\Console\Input\InputOption;

require_once 'recipe/common.php';

require_once 'deploy_laravel.php';

set('default_timeout', 3600);
// Project name
set('application', 'stage');

// Project repository *set your correct id and repository name
set('repository', 'git@github.com:your-id/repository.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', false);

// Shared files/dirs between deploys
add('shared_files', [
    '.env.testing'
]);
add('shared_dirs', [
    'public/contents'
]);

// Writable dirs by web server
add('writable_dirs', []);
set('allow_anonymous_stats', false);

set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader --no-suggest');

set('local_path', '~/Sites/your-domain');

host('stage')
    ->set('branch', 'develop')
    ->hostname('stage.your-domain.com')
    ->user('ubuntu')
    ->set('deploy_path', '~/{{application}}');

host('production')
    ->set('branch', 'master')
    ->hostname('your-domain.co.kr')
    ->user('ubuntu')
    ->set('deploy_path', '~/{{application}}');

task('db:backup', [
    'db:backup:download',
    'db:backup:import',
]);
# you can make your own command like below
task('deploy:ga:symlink', function () {
    $target = get('target');
    if($target == 'production'){
        writeln('<info>making custom config files symlinks</info>');
        run('{{bin/symlink}} -nfs {{deploy_path}}/shared/public/data {{release_path}}/public/data');
        run('{{bin/symlink}} -nfs {{deploy_path}}/shared/public/image {{release_path}}/public/image');
        run('{{bin/symlink}} -nfs {{deploy_path}}/shared/public/smart_editor {{release_path}}/public/smart_editor');
        run('{{bin/symlink}} -nfs {{deploy_path}}/shared/public/pic {{release_path}}/public/pic');
    }else{
        writeln('<info>Pass for stage</info>');
    }
});

desc('Feature Test');
option('has-backend-update', null, InputOption::VALUE_REQUIRED, 'Determine if we have a file have to test');
task('deploy:test', function (){
    /*$isSkip = input()->getOption('has-backend-update');
    if(!$isSkip){
        writeln('Skip this job by no update backend');
        return;
    }*/
    $target = get('target');
    if($target == 'stage'){
        run('cd {{release_path}} && vendor/bin/phpunit');
    }else{
        writeln('Skip this job on production stage');
    }
});
task('deploy:build', function(){
    run('npm install --loglevel=error && npm run build --loglevel=error');
})->local();

task('deploy:upload_build', function(){
    upload('public/dist/', '{{release_path}}/public/dist/');
});

task('deploy:queue-restart', function(){
    run('{{bin/php}} {{release_path}}/artisan horizon:purge');
    run('{{bin/php}} {{release_path}}/artisan horizon:terminate');
    run('{{bin/php}} {{release_path}}/artisan queue:restart');
});

task('deploy:composer', function(){
    $target = get('target');
    run('cp -r {{deploy_path}}/current/vendor {{release_path}}/vendor');
    writeln('<info>Vender directory copied</info>');
    if($target == 'production'){
        run('cd {{release_path}} && {{bin/composer}} install --no-dev --verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader --no-suggest');
    }else{
        run('cd {{release_path}} && {{bin/composer}} install --verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader --no-suggest');
    }
});

task('hello', function(){
    $stage = input()->getArgument('stage');
    writeln($stage);
});

task('deploy:hello', function(){
    $str = run('whoami');
    writeln('Hello, I am ' .$str);
});

/**
 * Main task
 */
desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:unlock',
    'deploy:lock',
    'deploy:release', // 깃헙 코드 배포
    'deploy:update_code',
    'deploy:shared',
    'deploy:ga:symlink', // 구버전 호환을 위한 심볼릭링크 생성
    'deploy:composer',
    'deploy:writable', // 파일 접근 권한 설정
    'deploy:upload_build',
    'artisan:migrate',
    'artisan:storage:link',
    //'artisan:package:discåover',
    'deploy:test', // stage 전용
    'deploy:queue-restart',
    //'artisan:view:cache',
    //'artisan:config:cache',
    //'artisan:optimize',
    'deploy:unlock',
    'deploy:symlink',
    'cleanup',
    'success',
]);

after('deploy:failed', 'deploy:unlock');
