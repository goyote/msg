<h2><a href="#kohana-msg-module" id="kohana-msg-module">Kohana MSG Module</a></h2>

"Flash messages" are one-time blurbs you display to users after they've done something <em>note-worthy</em>. They're typically used as confirmation or acknowledgment to a given action.

Unsurprisingly, they're very popular amongst web applications:

<p><img src="http://kohanaftw.com/wordpress/wp-content/uploads/2011/01/blurb1.png" alt="" title="blurb1" width="464" height="84" class="aligncenter size-full wp-image-143" /></p>

<p><img src="http://kohanaftw.com/wordpress/wp-content/uploads/2011/01/blurb2.png" alt="" title="blurb2" width="278" height="96" class="aligncenter size-full wp-image-144" /></p>

<p><img src="http://kohanaftw.com/wordpress/wp-content/uploads/2011/01/blurb3.png" alt="" title="blurb3" width="136" height="53" class="aligncenter size-full wp-image-145" /></p>

<p><img src="http://kohanaftw.com/wordpress/wp-content/uploads/2011/01/blurb4.png" alt="" title="blurb4" width="254" height="91" class="aligncenter size-full wp-image-146" /></p>

<div class="section">
<h3><a href="#installation" id="installation">Installation</a></h3>

<code>MSG</code> installs like any other module. If you're familiar with git, you can fire up a terminal, and add the module with the following command:

<pre class="brush:bash">
git submodule add git://github.com/goyote/msg.git modules/msg
</pre>

<div class="note">
<div>
<p>
<strong>Note:</strong> For a better description on how to add ko modules, please read <a href="http://kohanaframework.org/guide/kohana/tutorials/git" target="_blank">http://kohanaframework.org/guide/kohana/tutorials/git</a>.
</p>
</div>
</div>

Alternatevely, you can install <code>MSG</code> manually. To do so, first download the zip file from <a href="http://github.com/goyote/msg">http://github.com/goyote/msg</a> and extract the <code>msg</code> folder into your <code>modules</code> directory. Then open the bootstrap file, and modify the <code>Kohana::modules</code> function:

<pre class="brush:php">
Kohana::modules(array(
	...
	'msg' => MODPATH.'msg', // Blurb messaging system
</pre>
</div>

<div class="section">

<h3><a href="#usage" id="usage">Usage</a></h3>

<div class="section">
<h4><a href="#instance" id="instance">instance()</a></h4>

<pre class="brush:php">
// Get a MSG instance
$msg = MSG::instance();

// Use the cookie driver
$msg = MSG::instance(MSG::COOKIE);

// Same thing:
$msg = MSG::instance('cookie');
</pre>

Messages are stored in the <code>$_SESSION</code> by default, but you can choose to store them in a browser cookie instead by using the cookie driver as shown above.

To avoid typing <code>MSG::instance(MSG::COOKIE)</code> everytime, you can override the <code>$default</code> public static property:

<pre class="brush:php">
// Set 'cookie' as the default driver
MSG::$default = MSG::COOKIE;

// Moving forward cookie will be the default driver
$msg = MSG::instance();
</pre>
</div>

<div class="section">
<h4><a href="#set" id="set">set()</a></h4>

<code>set()</code> allows you to store a new message. The new messge is appended to an array and does not override anything. <code>set()</code> returns <code>$this</code> so it's chainable. If you need to store multiple messages of the same type (e.g. ERROR) then you can simply pass an array as the second argument. If you need to store multiple messages of diffrent types (e.g. SUCCESS and NOTICE) then method chaining is a good alternative.

<pre class="brush:php">
// Set a new error message
MSG::instance()->set(MSG::ERROR, 'lol you cant do that');

// Embed some values into the message (sprintf is used instead of strtr)
MSG::instance()->set(MSG::SUCCESS, __('%s now has %d monkeys'),
	array($this->user->first_name, count($monkeys)));
</pre>

As been said, arrays are also accepted.
<pre class="brush:php">
$post = new Validation($_POST);
...

MSG::instance()->set(MSG::ERROR, array_values($post->errors()));
</pre>
</div>

<div class="section">
<h4><a href="#get" id="get">get()</a></h4>

<code>get()</code> returns an array of messages. If no arguments are passed, <code>get()</code> will return everything. If you only want messages of a ceritan type (e.g. ERROR) then simply pass in the constant holding the type. You can also pass an array of types, or get everything except a certain type.

<pre class="brush:php">
// Get all messages
$messages = MSG::instance()->get();

// Only retrieve the error messages
$error_messages = MSG::instance()->get(MSG::ERROR);

// Retrieve alerts and warnings
$messages = MSG::instance()->get(array(MSG::ALERT, MSG::WARNING));

// Get messages of any type except errors and alerts
$messages = MSG::instance()->get(array(1 => array(MSG::ERROR, MSG::ALERT)));

// Override the default return value (NULL) with a custom string
$msgs = MSG::instance()->get(MSG::NOTICE, 'no messages found');

// $msgs === 'no messages found'
</pre>
</div>

<code>get()</code> returns the standard <code>NULL</code> value if no messages are found. You can modify the default value by passing in a second argument as shown above.

<div class="section">
<h4><a href="#get_once" id="get_once">get_once()</a></h4>

<code>get_once()</code> behaves exactly like <code>get()</code>, but the only difference is, <code>get_once()</code> deletes the messages after retrieval.

<pre class="brush:php">
// Get a singleton instance
$msg = MSG::instance();

// Get all the messages
$messages = $msg->get_once();

$msg->get(); // Returns NULL

// get_once also retrieves by type
$alert_messages = $msg->get_once(MSG::ALERT);
</pre>
</div>


<div class="section">
<h4><a href="#delete" id="delete">delete()</a></h4>

<pre class="brush:php">
// Delete all messages
MSG::instance()->delete();

// Delete only the warning messages
MSG::instance()->delete(MSG::WARNING);
</pre>
</div>

<div class="section">
<h4><a href="#render" id="render">render()</a></h4>

Finally, you'll want to render the messages in the easiest way possible. To do that, all you have to do is call <code>echo MSG::instance()->render()</code> anywhere in your view.


<pre class="brush:php">
&lt;div id="wrapper"&gt;
	...
	&lt;?php echo MSG::instance()->render() ?&gt;
</pre>

You can also, only render a specific type of message, e.g.

<pre class="brush:php">
&lt;!-- Render all the messages except the errors at the top of the page --&gt;
&lt;?php echo MSG::instance()->render(array(1 =&gt; array(MSG::ERROR))) ?&gt;
&lt;form ...&gt;
	&lt;!-- Now render the error messages --&gt;
	&lt;?php echo MSG::instance()->render(MSG::ERROR) ?&gt;
</pre>

You can also render the messages in a custom view.

<pre class="brush:php">
// Render the messages using the 'roar' mootools plugin (actual example provided with the module)
echo MSG::instance()->render(NULL, TRUE, 'msg/roar');
</pre>

<code>render()</code> returns a string.

You can modify the default view <code>msg/all</code> by copy-pasting a copy into your <code>application/views</code> directory, and making your mods there.
</div>

</div>

<div class="section">
<h3><a href="#types-of-messages" id="types-of-messages">Types of messages (constants)</a></h3>

<pre class="brush:php">
MSG::COOKIE
MSG::SESSION
MSG::ERROR
MSG::ALERT
MSG::NOTICE
MSG::WARNING
MSG::SUCCESS
MSG::ACCESS
</pre>
</div>