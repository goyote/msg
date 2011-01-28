<?php defined('SYSPATH') or die('No direct script access.') ?>
<html>
	<head>
		<link type="text/css" rel="stylesheet" href="/modules/msg/assets/css/Roar.css" />
	</head>
	<body>
		Example page (in the real world, you might want to escape the output ;))
		<script type="text/javascript" src="/modules/msg/assets/js/mootools-core.js"></script>
		<script type="text/javascript" src="/modules/msg/assets/js/mootools-more.js"></script>
		<script type="text/javascript" src="/modules/msg/assets/js/Roar.js"></script>
		<script type="text/javascript">
		window.addEvent('domready', function() {
			(function() {
				var messages = <?php echo json_encode($messages) ?>,
					roar = new Roar();
				for (var i = 0, len = messages.length; i < len; i++) {
					var message = messages[i];
					roar.alert(((function(string) {
						return string.charAt(0).toUpperCase() + string.slice(1);
					})(message['type'])+'!'), message['text']);
				}
			})();
		});
		</script>
	</body>
</html>