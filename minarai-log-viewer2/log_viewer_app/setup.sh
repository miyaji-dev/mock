#!/bin/bash

#############################
# プロジェクト初期設定
#############################

# ディレクトリのパーミッションを変更
chmod 0777 ./logs

# composerのでインストールを実行
curr_dir=pwd
cd ./libs
./composer.phar install
cd $curr_dir

exit 0
