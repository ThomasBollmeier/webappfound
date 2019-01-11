<?php
$defaultController = $routerData->defaultAction->controllerName;
$defaultAction = $routerData->defaultAction->actionName;
?>
<?= "<?php" ?>

<?php if (!empty($namespace)) { echo "namespace $namespace;\n"; }?>

<?php if (empty($baseRouterAlias)) { ?>
use tbollmeier\webappfound\routing\Router;
<?php } else { ?>
use tbollmeier\webappfound\routing\Router as <?= $baseRouterAlias ?>;
<?php } ?>
use tbollmeier\webappfound\routing\RouterData;
use tbollmeier\webappfound\routing\ControllerData;
use tbollmeier\webappfound\routing\ActionData;
use tbollmeier\webappfound\routing\DefaultActionData;

<?php if (empty($baseRouterAlias)) { ?>
class <?= $className ?> extends Router
<?php } else { ?>
class <?= $className ?> extends <?= $baseRouterAlias ?>
<?php } ?>
{
    public function __construct(
        $controllerNS = "",
        $baseUrl = "")
    {
        parent::__construct([
            "controllerNS" => $controllerNS,
            "defaultCtrlAction" => <?= "\"$defaultCtrlAction\"" ?>,
            "baseUrl" => $baseUrl ]);

        $routerData = new RouterData();

        $routerData->defaultAction = new DefaultActionData();
        $routerData->defaultAction->controllerName = <?= "\"${defaultController}\"" ?>;
        $routerData->defaultAction->actionName = <?= "\"${defaultAction}\"" ?>;

        $routerData->controllers = [];
<?php foreach ($routerData->controllers as $controller) {
    $controllerName = $controller->name; ?>

        $controller = new ControllerData();
        $controller->name = <?= "\"$controllerName\""?>;
    <?php foreach ($controller->actions as $action) {
        $actionName = $action->name;
        $actionHttpMethod = $action->httpMethod;
        $actionPattern = $action->pattern;
        $actionParamNames = implode(", ", array_map(
            function ($name) { return "\"$name\""; },
            $action->paramNames));
        ?>

        $action = new ActionData();
        $action->name = <?= "\"$actionName\""?>;
        $action->httpMethod = <?= "\"$actionHttpMethod\""?>;
        $action->pattern = <?= "\"$actionPattern\""?>;
        $action->paramNames = <?= "[$actionParamNames]" ?>;
        $controller->actions[] = $action;
    <?php } ?>

        $routerData->controllers[] = $controller;
<?php }?>

        $this->setUpHandlers($routerData);
    }
}