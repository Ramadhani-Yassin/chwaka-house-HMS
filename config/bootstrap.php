<?php
/*
Ramadhani
*/

$container_builder = new Core_Business_ContainerBuilder;
$container = $container_builder->build();
Adapter_ServiceLocator::setServiceContainerInstance($container);
