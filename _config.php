<?php
$base = BASE_URL ? BASE_URL : '/';
$frameworkDir = FRAMEWORK_DIR;

Config::inst()->update('SecureFileExtension', 'access_config', array(
	'Apache' => array(
		'file' => '.htaccess',
		'content' => <<<EOF
RewriteEngine On
RewriteBase $base
RewriteCond %{REQUEST_URI} ^(.*)$
RewriteRule .* $frameworkDir/main.php?url=%1 [QSA]
EOF
	),
	'IIS' => array(
		'file' => 'web.config',
		'content' => <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<configuration>
	<system.webServer>
		<rewrite>
			<rules>
				<rule name="SilverStripe Clean URLs" stopProcessing="true">
					<match url="^(.*)$" />
					<action type="Rewrite" url="$frameworkDir/main.php?url={R:1}" appendQueryString="true" />
				</rule>
			</rules>
		</rewrite>
		<httpErrors errorMode="Detailed">
		</httpErrors>
	</system.webServer>
</configuration>
EOF
	)
));

