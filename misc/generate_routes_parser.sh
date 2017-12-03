#!/usr/bin/env bash

php ../vendor/tbollmeier/parsian/scripts/parsiangen.php \
 --namespace=tbollmeier\\webappfound\\routing \
 --parser=RoutesBaseParser ./routes.parsian > ../src/routing/RoutesBaseParser.php