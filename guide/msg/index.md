# Kohana MSG Module

"Flash messages" are one-time blurbs you display to users after they've done something *note-worthy*. They're typically used as confirmation or acknowledgment to a given action.

Unsurprisingly, they're very popular amongst web applications:

![blurb screenshot](blurb2.png)

![blurb screenshot](blurb3.png)

![blurb screenshot](blurb4.png)

![blurb screenshot](blurb1.png)

## Installation

`MSG` installs like any other module. If you're familiar with git, you can fire up a terminal, and add the module with the following command:

    git submodule add git://github.com/goyote/msg.git modules/msg

[!!] **Note:** For a better description on how to add ko modules, please read [http://kohanaframework.org/guide/kohana/tutorials/git](http://kohanaframework.org/guide/kohana/tutorials/git).

Alternatevely, you can install `MSG` manually. To do so, first download the zip file from [http://github.com/goyote/msg](http://github.com/goyote/msg) and extract the `msg` folder into your `modules` directory. Then open the bootstrap file, and modify the `Kohana::modules` function:

    Kohana::modules(array(
        ...
        'msg' => MODPATH.'msg', // Blurb messaging system

## Usage

### instance()

    // Get a MSG instance
    $msg = MSG::instance();
    
    // Use the cookie driver
    $msg = MSG::instance(MSG::COOKIE);
    
    // Same thing:
    $msg = MSG::instance('cookie');

Messages are stored in the `$_SESSION` by default, but you can choose to store them in a browser cookie instead by using the cookie driver as shown above.

To avoid typing `MSG::instance(MSG::COOKIE)` everytime, you can override the `$default` public static property:

    // Set 'cookie' as the default driver
    MSG::$default = MSG::COOKIE;
    
    // Moving forward cookie will be the default driver
    $msg = MSG::instance();

### set()

`set()` allows you to store a new message. The new messge is appended to an array and does not override anything. `set()` returns `$this` so it's chainable. If you need to store multiple messages of the same type (e.g. ERROR) then you can simply pass an array as the second argument. If you need to store multiple messages of diffrent types (e.g. SUCCESS and NOTICE) then method chaining is a good alternative.

    // Set a new error message
    MSG::instance()->set(MSG::ERROR, 'lol you cant do that');
    
    // Embed some values into the message (sprintf is used instead of strtr)
    MSG::instance()->set(MSG::SUCCESS, __('%s now has %d monkeys'),
        array($this->user->first_name, count($monkeys)));

As been said, arrays are also accepted.

    $post = new Validation($_POST);
    ...
    
    MSG::instance()->set(MSG::ERROR, array_values($post->errors()));

### get()

`get()` returns an array of messages. If no arguments are passed, `get()` will return everything. If you only want messages of a ceritan type (e.g. ERROR) then simply pass in the constant holding the type. You can also pass an array of types, or get everything except a certain type.

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

`get()` returns the standard `NULL` value if no messages are found. You can modify the default value by passing in a second argument as shown above.

### get_once()

`get_once()` behaves exactly like `get()`, but the only difference is, `get_once()` deletes the messages after retrieval.

    // Get a singleton instance
    $msg = MSG::instance();
    
    // Get all the messages
    $messages = $msg->get_once();
    
    $msg->get(); // Returns NULL
    
    // get_once also retrieves by type
    $alert_messages = $msg->get_once(MSG::ALERT);

### delete()

    // Delete all messages
    MSG::instance()->delete();
    
    // Delete only the warning messages
    MSG::instance()->delete(MSG::WARNING);

### render()

Finally, you'll want to render the messages in the easiest way possible. To do that, all you have to do is call `echo MSG::instance()->render()` anywhere in your view.

    &lt;div id="wrapper"&gt;
        ...
        &lt;?php echo MSG::instance()->render() ?&gt;

You can also, only render a specific type of message, e.g.

    &lt;!-- Render all the messages except the errors at the top of the page --&gt;
    &lt;?php echo MSG::instance()->render(array(1 =&gt; array(MSG::ERROR))) ?&gt;
    &lt;form ...&gt;
        &lt;!-- Now render the error messages --&gt;
        &lt;?php echo MSG::instance()->render(MSG::ERROR) ?&gt;

You can also render the messages in a custom view.

    // Render the messages using the 'roar' mootools plugin (actual example provided with the module)
    echo MSG::instance()->render(NULL, TRUE, 'msg/roar');

`render()` returns a string.

You can modify the default view `msg/all` by copy-pasting a copy into your `application/views` directory, and making your mods there.

## Types of messages (constants)

    MSG::COOKIE
    MSG::SESSION
    MSG::ERROR
    MSG::ALERT
    MSG::NOTICE
    MSG::WARNING
    MSG::SUCCESS
    MSG::ACCESS
    MSG::CRITICAL