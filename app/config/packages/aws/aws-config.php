<?php return array(
	'class' => 'Aws\Common\Aws',
	'services' => array(
		'default_settings' => array(
			'params' => array()
		),
		'autoscaling' => array(
			'alias'   => 'AutoScaling',
			'extends' => 'default_settings',
			'class'   => 'Aws\AutoScaling\AutoScalingClient'
		),
		'cloudformation' => array(
			'alias'   => 'CloudFormation',
			'extends' => 'default_settings',
			'class'   => 'Aws\CloudFormation\CloudFormationClient'
		),
		// ...
	)
);