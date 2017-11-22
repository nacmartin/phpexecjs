<?php

namespace Nacmartin\PhpExecJs\Runtime;

use Symfony\Component\Process\Process;

class ExternalRuntime implements RuntimeInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    public $temporaryFiles = array();

    /**
     * @var string
     */
    public $context = null;

    /**
     * env.
     *
     * @var array|null The environment variables or null to use the same environment as the current PHP process
     */
    public $env = null;

    /**
     * Timeout for the eval.
     *
     * @var bool
     */
    public $timeout = false;

    /**
     * NodeJs binary.
     *
     * @var array
     */
    private $binary;

    /**
     * Constructor.
     *
     * @param string|null   $name   the name of runtime
     * @param array|array() $binary the name of the binary command (ex. node)
     * @param array|null    $env    The environment variables or null to use the same environment as the current PHP process
     */
    public function __construct($name, $binary = array(), $env = null)
    {
        $this->name = $name;
        $this->env = $env;
        $this->binary = $binary;
        $this->binaryPath = $this->findBinaryPath();
        register_shutdown_function(array($this, 'removeTemporaryFiles'));
    }

    public function __destruct()
    {
        $this->removeTemporaryFiles();
    }

    public function getName()
    {
        return $this->name;
    }

    public function isAvailable()
    {
        return $this->findBinaryPath() ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function evalJs($code)
    {
        $code = 'return eval('.json_encode($code).');';
        if ($this->context) {
            $code = $this->context."\n".$code;
        }
        $code = $this->embedInRuntime($code);
        $sourceFile = $this->createTemporaryFile($code, 'js');

        $command = $this->binaryPath;
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            $command = '"'.$this->binaryPath.'"'; // wrap the command in double quotes http://stackoverflow.com/a/36494732/5359860
        }
        $escapedBinary = escapeshellarg($this->binaryPath);
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
     * {@inheritdoc}
     */
    public function call($function, $arguments = array())
    {
        return $this->evalJs($function.'.apply(this, '.json_encode($arguments).')');
    }

    /**
     * Embeds the code to eval in an environment that provides status of the result.
     *
     * @param string $code
     */
    public function embedInRuntime($code)
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
     * Sets the timeout. Be aware that option only works with symfony.
     *
     * @param int $timeout The timeout to set
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Get TemporaryFolder
     * From Knp/Snappy (kudos).
     *
     * @return string
     */
    public function getTemporaryFolder()
    {
        return sys_get_temp_dir();
    }

    /**
     * Removes all temporary files
     * From Knp/Snappy (kudos).
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
     * From Knp/Snappy (kudos).
     *
     * @param string $content   Optional content for the temporary file
     * @param string $extension An optional extension for the filename
     *
     * @return string The filename
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
        $filename = $dir.DIRECTORY_SEPARATOR.uniqid('nacmartin_phpexecjs', true);
        if (null !== $extension) {
            $filename .= '.'.$extension;
        }
        if (null !== $content) {
            file_put_contents($filename, $content);
        }
        $this->temporaryFiles[] = $filename;

        return $filename;
    }

    /**
     * Wrapper for the "unlink" function
     * From Knp/Snappy (kudos).
     *
     * @param string $filename
     *
     * @return bool
     */
    protected function unlink($filename)
    {
        return file_exists($filename) ? unlink($filename) : false;
    }

    protected function findBinaryPath()
    {
        $pathStr = getenv('PATH');
        $paths = explode(PATH_SEPARATOR, $pathStr);
        foreach ($paths as $path) {
            foreach ($this->binary as $binary) {
                $binaryPath = $path.DIRECTORY_SEPARATOR.$binary;
                if (is_executable($binaryPath)) {
                    return $binaryPath;
                }
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $binaryPath = $binaryPath.'.exe';
                    if (is_executable($binaryPath)) {
                        return $binaryPath;
                    }
                }
            }
        }

        foreach ($this->binary as $binary) {
            if ($wichBinaryPath = exec('which '.$binary)) {
                return $wichBinaryPath;
            }
        }

        return;
    }

    /**
     * Checks the process return status
     * From Knp/Snappy (kudos).
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
     * Checks the eval return status.
     *
     * @param srtring $statusEval
     * @param string  $result
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
     * From Knp/Snappy (kudos).
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
     * {@inheritdoc}
     */
    public function createContext($code, $cacheName = null)
    {
        $this->context = $code;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCache()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setCache($cache)
    {
        throw new \Exception("External runtime (node.js) doesn't support cache. You may try installing v8JS php extension so it is used instead of this one.");
    }
}
