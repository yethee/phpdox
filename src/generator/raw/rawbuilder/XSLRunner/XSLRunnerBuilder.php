<?php
/**
 * Copyright (c) 2010-2011 Arne Blankerts <arne@blankerts.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 *   * Neither the name of Arne Blankerts nor the names of contributors
 *     may be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT  * NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER ORCONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    phpDox
 * @author     Arne Blankerts <arne@blankerts.de>
 * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
 * @license    BSD License
 */
namespace TheSeer\phpDox {

    use \TheSeer\fDOM\fDOMDocument;
    use \TheSeer\fDOM\fDOMElement;
    use \Carica\Xsl\Runner\Streamwrapper\PathMapper;

    require 'CallbackHandler.php';

    class XSLRunnerBuilder implements RawBuilderInterface {

        protected $generator;

        public function setUp(AbstractGenerator $generator) {
            $this->generator = $generator;
        }

        public function run(Factory $factory, ProgressLogger $logger) {

            PathMapper::register(
              'source',
              $this->generator->getXMLDirectory().'/'
            );
            PathMapper::register(
              'target',
              $this->generator->getOutputDirectory() . '/',
              PathMapper::CREATE_DIRECTORIES | PathMapper::WRITE_FILES
            );

            //$directory->copy(dirname($templateFile).'/files/', $directoryOutput.'/files/');

            $xsl = $this->generator->getXSLTProcessor('XSLBuilder/start.xsl');
            $xsl->registerPHPfunctions(
                array(
                    '\\Carica\\Xsl\\Runner\\XsltCallback'
                )
            );

            $dom = new \DOMDocument();
            $dom->appendChild($dom->createElement('project'));

            $res = $xsl->transformToDoc($dom);
            $this->generator->copyStatic('XSLBuilder/static', true);

        }


        /**
        * Callback for xsl: load an xml document
        *
        * @param string $url
        * @return \DOMDocument
        */
        static public function XsltCallback($class) {
          $class = 'Carica\\Xsl\\Runner\\Callback\\'.$class;
          if ($offset = strpos($class, '::')) {
            $method = substr($class, $offset + 2);
            $class = substr($class, 0, $offset);
          } else {
            $method = NULL;
          }
          $callback = new $class();
          if ($callback instanceOf \Carica\Xsl\Runner\Callback) {
            $arguments = func_get_args();
            array_shift($arguments);
            if ($method) {
              return call_user_func_array(array($callback, $method), $arguments);
            } else {
              return call_user_func_array($callback, $arguments);
            }
          } else {
            throw new \UnexpectedValueException(
              sprintf('Invalid callback: "%s".', $class)
            );
          }
        }
    }
}