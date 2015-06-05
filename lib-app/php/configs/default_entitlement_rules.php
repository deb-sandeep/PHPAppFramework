<?php

$DEFAULT_ENTITLEMENTS = <<< EOT
{
	"property" : {
		"permissible_ops" : [
			"READ", "WRITE", "UPDATE"
		],
		"default_indifferent_ops" : [
			"READ"
		],
		"default_conflict_strategy" : "deny"
	},
	"chapter" : {
		"permissible_ops" : [
			"NOTES", "FLASH_CARD", "CHAPTER_STATS"
		],
		"default_indifferent_ops" : [],
		"default_conflict_strategy" : "deny"
	}
}
EOT;

?>