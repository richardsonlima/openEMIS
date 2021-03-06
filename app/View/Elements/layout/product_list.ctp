<?php
$server = ($_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . DS;
$env = basename($this->webroot);
$products = array(
	'Visualizer' => array('icon' => 'kd-Openemis kd-visualizer', 'name' => 'visualizer')
);
?>

<div class="btn-group">
	<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
		<i class="fa fa-list fa-lg"></i>
	</a>

	<ul aria-labelledby="dropdownMenu" role="menu" class="dropdown-menu">
	<?php foreach ($products as $name => $item) : ?>
		<li>
			<a href="#">
				<i class="<?php echo $item['icon'] ?>"></i>
				<span style="margin-left: 5px;"><?php echo $name ?></span>
			</a>
		</li>
	<?php endforeach ?>
	</ul>
</div>
