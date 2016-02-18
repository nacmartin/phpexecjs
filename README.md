# PhpExecJs

PhpExecJS lets you run JavaScript code from PHP.

Short example:

    print_r($phpexecjs->evalJs("'red yellow blue'.split(' ')"));

Will print:

    Array
    (
        [0] => red
        [1] => yellow
        [2] => blue
    )

[![Build Status](https://travis-ci.org/nacmartin/phpexecjs.svg?branch=master)](https://travis-ci.org/nacmartin/phpexecjs)
[![Latest Stable Version](https://poser.pugx.org/nacmartin/phpexecjs/v/stable)](https://packagist.org/packages/nacmartin/phpexecjs)
[![Latest Unstable Version](https://poser.pugx.org/nacmartin/phpexecjs/v/unstable)](https://packagist.org/packages/nacmartin/phpexecjs)
[![License](https://poser.pugx.org/nacmartin/phpexecjs/license)](https://packagist.org/packages/nacmartin/phpexecjs)

# Installation

    composer require nacmartin/phpexecjs

Sample program

# Usage

    <?php
    require __DIR__ . '/../vendor/autoload.php';
    
    use Nacmartin\PhpExecJs\PhpExecJs;
    
    $phpexecjs = new PhpExecJs();
    
    print_r($phpexecjs->evalJs("'red yellow blue'.split(' ')"));

Will print:

    Array
    (
        [0] => red
        [1] => yellow
        [2] => blue
    )


# Using contexts

You can set up a context, like libraries and whatnot, that you want to use in your eval'd code. This is used for instance by the [ReactBundle](https://github.com/limenius/ReactBundle/) to render React server-side.

For instance, we can compile CoffeeScript using this feature:

    $phpexecjs->createContextFromFile("http://coffeescript.org/extras/coffee-script.js");
    print_r($phpexecjs->call("CoffeeScript.compile", ["square = (x) -> x * x", ['bare' => true]]));


That will print:

      var square;
    
      square = function(x) {
        return x * x;
      };
    
You can extend this example to do things like use this function as context:

    $square  = $phpexecjs->createContext($phpexecjs->call("CoffeeScript.compile", ["square = (x) -> x * x", ['bare' => true]]));
    print_r($phpexecjs->evalJs('square(3)'));
    
That will print `3`.

This can be used for instance, to use CoffeeScript or compile templates in JavaScript templating languages. 

# Constructor arguments

`new PhpExecJs($binary = null, $env = null);` accepts two arguments:

* `binary` is a string with the command used to call node. It defaults to `/usr/bin/env node`.
* `env` is an array with environment variables or null to use the same environment as the current PHP process.

# How it works

When you run `evalJs`, the code will be inserted in a small wrapper used to run JavaScript's `eval()` against your code and check the status for error handling.

If you set up a context, the code will be inserted before the call to `eval()` in JavaScript.

# Future work

One of the goals of this library is to be able to choose among the available runtimes (PHPJS, node.js, Spidermonkey). Currently it only supports node.js . If you want to contribute with a runner, you are more than welcome.


# Credits

This library is inspired in [ExecJs](https://github.com/rails/execjs), A Ruby library.

The code used to manage processes and temporary files has been adapted from the [Snappy](https://github.com/KnpLabs/snappy) library by KNP Labs.
