<?php
/* Initiate Page */
echo $this->html->docType();
echo "\n";
echo $this->html->roottag();
echo "\n";
?>
<head>
    <title><?php echo $this->GetConfig('site_title'); ?> : <?php echo $this->GetConfig('page_title'); ?></title>
<?php
	echo $this->html->charset();
	echo "\n";
	echo $this->html->meta(array('keywords' => ''));
	echo $this->html->meta(array('description' => ''));
	echo $this->html->meta(array('author' => ''));
	echo $this->html->meta(array('robots' => 'follow,index'));
	echo $this->html->meta(array('revisit-after' => '15 Days'));
	echo $this->html->meta(array('language' => 'english'));
	echo $this->html->css('flite');
	echo !$this->PDIsEmpty('css') ? $this->html->css($this->GetPD('css')) : '';
	echo $this->html->css('ie6', "IE 6");
	echo $this->html->css('ie7', "IE 7");
	?>
    <meta name="viewport" content="width=1024, initial-scale=1" />
    <link rel="icon" href="<?php echo $this->html->StaticDomain(); ?>/favicon.ico" type="image/vnd.microsoft.icon" />
    <link rel="shortcut icon" href="<?php echo $this->html->StaticDomain(); ?>/favicon.ico" type="image/vnd.microsoft.icon" />
</head>

<body>
