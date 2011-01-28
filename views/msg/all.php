<ul id="messages">
	<?php foreach ($messages as $message): ?>
		<li class="<?php echo HTML::chars($message['type']) ?>">
			<p><?php echo HTML::chars($message['text']) ?></p>
		</li>
	<?php endforeach ?>
</ul>