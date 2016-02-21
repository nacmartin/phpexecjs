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

    $square = $phpexecjs->call("CoffeeScript.compile", ["square = (x) -> x * x", ['bare' => true]]);
    $phpexecjs->createContext($square);
    print_r($phpexecjs->evalJs('square(3)'));
    
That will print `9`.

This can be used for instance, to use CoffeeScript or compile templates in JavaScript templating languages. 

# How it works

When you run `evalJs`, the code will be inserted into a small wrapper used to run JavaScript's `eval()` against your code and check the status for error handling.

If you set up a context, the code will be inserted before the call to `eval()` in JavaScript, and if you have [the V8Js extension](https://github.com/phpv8/v8js) installed, it will precompile it.

# Runtimes supported

By default, PhPExecjs will auto-detect the best runtime available. Currently, the routines supported are:

* [V8Js (PHP extension)](https://github.com/phpv8/v8js)
* node.js

It is recommended to have V8Js installed, but you may want to have it installed in production and still be able to use PhpExecJs calling node as a subprocess during development, so you don't need to install the extension.

## Adding a external runtime

If you have a external runner (let's say, Spidermonkey), and you want to use it, pass it to the constructor:


    $myRuntime = new ExternalRuntime('My runtime name', 'my_command');
    $phpExecJs = new PhpExecJs($myRuntime);

## Contributing with runtimes

We would like to support more runtimes (Duktape, for instance). If you want to contribute with a runtime, it is pretty simple. You just have to implement `src/Runtimes/RuntimeInterface`. Check the directory `src/Runtimes` for examples.

# Credits

This library is inspired in [ExecJs](https://github.com/rails/execjs), A Ruby library.

The code used to manage processes and temporary files has been adapted from the [Snappy](https://github.com/KnpLabs/snappy) library by KNP Labs.
