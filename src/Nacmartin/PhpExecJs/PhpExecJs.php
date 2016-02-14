<?php

namespace Nacmartin\PhpExecJs;
use Symfony\Component\Process\Process;

class PhpExecJs
{
    /**
     * @var array
     */
    public $temporaryFiles = array();

    /**
     * @var string
     */
    public $context = null;

    /**
     * env
     * 
     * @var array|null The environment variables or null to use the same environment as the current PHP process
     * @access public
     */
    public $env = null;

    /**
     * Timeout for the eval
     * 
     * @var bool
     * @access public
     */
    public $timeout = false;

    /**
     * NodeJs binary
     * 
     * @var string
     * @access private
     */
    private $binary;

    /**
     * Constructor
     * 
     * @param string|null $binary The environment variables or null to use the same environment as the current PHP process
     * @param array|null $env 
     * @access public
     * @return void
     */
    public function __construct($binary = null, $env = null)
    {
        $this->binary = $binary ? : '/usr/bin/env node';
        $this->env = $env;
        register_shutdown_function(array($this, 'removeTemporaryFiles'));
    }

    public function __destruct()
    {
        $this->removeTemporaryFiles();
    }

    public function createContextFromFile($filename)
    {
        $this->createContext(file_get_contents($filename));
    }
    /**
     * Stores code as context, so we can eval other JS with this context
     *
     * @param $code string
     * @return void
     */
    public function createContext($code)
    {
        $this->context = $code;
    }

    /**
     * Evaluates JS code and returns the output
     *
     * @param $code string Code to evaulate
     * @returns string
     */
    public function evalJs($code)
    {
        $code = "return eval(".json_encode($code).');';
        if ($this->context) {
            $code = $this->context."\n".$code;
        }
        $code = $this->embedInRunner($code);
        $sourceFile = $this->createTemporaryFile($code, 'js');

        $command = $this->binary;
        $escapedBinary = escapeshellarg($this->binary);
        if (is_executable($escapedBinary)) {
            $command = $escapedBinary;
        }
        $command .= ' '.$sourceFile;

        list($status, $stdout, $stderr) = $this->executeCommand($command);
        $this->checkProcessStatus($status, $stdout, $stderr, $command);

        list($statusEval, $result) = json_decode($stdout, true);
        $this->checkEvalStatus($statusEval, $result);
        return $result;
    }

    /**
     * Calls a JavaScript function against an array of arguments
     * 
     * @param string $function 
     * @param array $arguments 
     * @return string
     */
    public function call($function, $arguments = array())
    {
        return $this->evalJs($function.'.apply(this, '.json_encode($arguments).')');
    }

    /**
     * Embeds the code to eval in an environment that provides status of the result
     * 
     * @param string $code
     * @access public
     * @return void
     */
    public function embedInRunner($code)
    {
        $embedded = <<<JS
(function(program, execJS) { execJS(program) })(function(global, module, exports, require, console, setTimeout, setInterval, clearTimeout, clearInterval, setImmediate, clearImmediate) { $code;
}, function(program) {
  var output, print = function(string) {
    process.stdout.write('' + string);
  };
  try {
    result = program();
    if (typeof result == 'undefined' && result !== null) {
      print('["ok"]');
    } else {
      try {
        print(JSON.stringify(['ok', result]));
      } catch (err) {
        print(JSON.stringify(['err', '' + err, err.stack]));
      }
    }
  } catch (err) {
    print(JSON.stringify(['err', '' + err, err.stack]));
  }
});
JS;
        return $embedded;
    }

    /**
     * Sets the timeout. Be aware that option only works with symfony
     *
     * @param integer $timeout The timeout to set
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Checks the process return status
     * From Knp/Snappy (kudos)
     *
     * @param int    $status  The exit status code
     * @param string $stdout  The stdout content
     * @param string $stderr  The stderr content
     * @param string $command The run command
     *
     * @throws \RuntimeException if the output file generation failed
     */
    protected function checkProcessStatus($status, $stdout, $stderr, $command)
    {
        if (0 !== $status and '' !== $stderr) {
            throw new \RuntimeException(sprintf(
                'The exit status code \'%s\' says something went wrong:'."\n"
                .'stderr: "%s"'."\n"
                .'stdout: "%s"'."\n"
                .'command: %s.',
                $status, $stderr, $stdout, $command
            ));
        }
    }

    /**
     * Checks the eval return status
     * 
     * @param srtring $statusEval 
     * @param string $result 
     * @access protected
     * @return void
     */
    protected function checkEvalStatus($statusEval, $result)
    {
        if ('ok' != $statusEval) {
            throw new \RuntimeException(sprintf(
                'Something went wrong evaluating JS code:'."\n"
                .'result: "%s"',
                $result
            ));
        }
    }

    /**
     * Executes the given command via shell and returns the complete output as
     * a string
     * From Knp/Snappy (kudos)
     *
     * @param string $command
     *
     * @return array(status, stdout, stderr)
     */
    protected function executeCommand($command)
    {
        $process = new Process($command, null, $this->env);
        if (false !== $this->timeout) {
            $process->setTimeout($this->timeout);
        }
        $process->run();
        return array(
            $process->getExitCode(),
            $process->getOutput(),
            $process->getErrorOutput(),
        );
    }

    /**
     * Get TemporaryFolder
     * From Knp/Snappy (kudos)
     *
     * @return string
     */
    public function getTemporaryFolder()
    {
        return sys_get_temp_dir();
    }

    /**
     * Removes all temporary files
     * From Knp/Snappy (kudos)
     */
    public function removeTemporaryFiles()
    {
        foreach ($this->temporaryFiles as $file) {
            $this->unlink($file);
        }
    }

    /**
     * Creates a temporary file.
     * The file is not created if the $content argument is null
     * From Knp/Snappy (kudos)
     *
     * @param string $content   Optional content for the temporary file
     * @param string $extension An optional extension for the filename
     *
     * @return string The filename
     *
     */
    protected function createTemporaryFile($content = null, $extension = null)
    {
        $dir = rtrim($this->getTemporaryFolder(), DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf("Unable to create directory: %s\n", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf("Unable to write in directory: %s\n", $dir));
        }
        $filename = $dir . DIRECTORY_SEPARATOR . uniqid('nacmartin_phpexecjs', true);
        if (null !== $extension) {
            $filename .= '.'.$extension;
        }
        if (null !== $content) {
            file_put_contents($filename, $content);
        }
        $this->temporaryFiles[] = $filename;
        return $filename;
    }

}
