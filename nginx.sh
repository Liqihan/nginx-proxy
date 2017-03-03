#! /usr/bin/env bash
# -*- coding: utf-8 -*-
# vim: tabstop=4 expandtab shiftwidth=4 softtabstop=4 fileencoding=utf-8 ff=unix ft=sh
# author: grove

selfName=$(readlink -f $0)
rootPath=$(readlink -f $(dirname ${selfName}))

nginxPath=$(which nginx)
${nginxPath} -p ${rootPath} -c ${rootPath}/conf/nginx-release.conf $@
