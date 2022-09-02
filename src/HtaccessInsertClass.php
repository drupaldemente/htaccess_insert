<?php
namespace Drupal\htaccess_insert;

class HtaccessInsertClass {

    const START_LINE = '###Star of data included by htaccess insert module###';

    const END_LINE = '###End of data included by htaccess insert module###';

    /**
     * Create new htaccess file and put en temporal folder.
     *
     * @param string $base
     * @param string $additional
     * @return string|null Return path of htaccess file created.
     */
    public function createNewHtaccess(string $base, string $additional){
        try {
            $from = null;
            $output = null;
            $resultCode = null;
            $tmpFolder = \Drupal::service('file_system')->getTempDirectory();

            // Get .htaccess data and remove htaccess_rewrite lines inserted.
            if( !isset($base) && !isset($additional)){
                $config = \Drupal::config('htaccess_insert.settings');
                $base = $config->get('base');
                $additional = $config->get('additional');
            }

            $htaccessFilePath = ( $base != '' && file_exists($base) ) ? $base : DRUPAL_ROOT.'/.htaccess';

            $originalHtaccess = file_get_contents( $htaccessFilePath);
            $htaccessLines = explode(PHP_EOL, $originalHtaccess);
            foreach($htaccessLines as $key => $line){
                $stringToFind = is_null($from) ? self::START_LINE : self::END_LINE;
                $pos = strpos($line, $stringToFind);
                if(is_null($from) && $pos !== FALSE ){
                    $from = $key;
                }
                if( !is_null($from) ){
                    unset($htaccessLines[$key]);
                    if($pos !== FALSE && $stringToFind != self::START_LINE){
                        break 1;
                    }
                }
            }

            // Build new .htaccess.
            $newSentences = array_values(array_filter(explode(PHP_EOL, $additional)));
            $htaccessLines[] = self::START_LINE;
            $htaccessLines = array_merge(array_values($htaccessLines), array_values($newSentences));
            $htaccessLines[] = self::END_LINE;
            if( !is_dir( $tmpFolder.'/htaccess' ) )
                mkdir( $tmpFolder.'/htaccess', 0777, true );
            file_put_contents( $tmpFolder.'/htaccess/htaccessTemp', implode(PHP_EOL, $htaccessLines ) );

            // Get validator script route.
            $module_handler = \Drupal::service('module_handler');
            $module_path = $module_handler->getModule('htaccess_insert')->getPath();
            $command = DRUPAL_ROOT.'/'.$module_path.'/bin/htaccess-validator.sh '.$tmpFolder.'/htaccess/htaccessTemp';

            // Execute script, check syntax and send result message.
            exec($command, $output, $resultCode);
            if(!is_null($resultCode) && $resultCode != 0 ){
                \Drupal::logger('htaccess_insert')->error(implode(', ', $output));
                return null;
            }
        } catch (Exception $e) {
            \Drupal::logger('htaccess_insert')->error('Exception: ',  $e->getMessage());
            return null;
        }
        return $tmpFolder.'/htaccess/htaccessTemp';
    }

    /**
     * This function updates the htaccess file according to the previously saved configuration.
     * @return bool return true when it's updated correctly.
     */
    public static function updateHtaccess($createFile = false) {
        try {
            if($createFile)
                self::createNewHtaccess();

            // Move htaccess file from temp to Drupal root folder.
            $tmpFolder = \Drupal::service('file_system')->getTempDirectory();
            if(file_exists($tmpFolder.'/htaccess/htaccessTemp') ){
                if (!copy($tmpFolder.'/htaccess/htaccessTemp', DRUPAL_ROOT.'/.htaccess')) {
                    \Drupal::logger('htaccess_insert')->error('error copying '.$tmpFolder.'/htaccess/htaccessTemp file to destination '.DRUPAL_ROOT.'/.htaccess');
                }else{
                    \Drupal::logger('htaccess_insert')->info('the user @user updated the htacces file',  ['@user' => \Drupal::currentUser()->getAccountName() ] );
                    \Drupal::messenger()->addStatus('htaccess is updated');
                    unlink($tmpFolder.'/htaccess/htaccessTemp');
                    return true;
                }
            }
        }
        catch (Exception $e) {
            \Drupal::logger('htaccess_insert')->error('Exception: ',  $e->getMessage());
        }
        return false;
    }

    /**
     * This function validates the syntax of the .htaccess file.
     * @param $filePath
     * @return bool
     */
    public static function validatorHtaccess($filePath){
        $output=null;
        $resultCode=null;
        try {
            // Get validator script route.
            $module_handler = \Drupal::service('module_handler');
            $module_path = $module_handler->getModule('htaccess_insert')->getPath();
            $command = DRUPAL_ROOT.'/'.$module_path.'/bin/htaccess-validator.sh ';
            $command .= !isset($filePath) ? DRUPAL_ROOT.'/.htaccess' : $filePath;

            // Execute script, validate sintaxis and send result message.
            exec($command, $output, $resultCode);
            if(!is_null($resultCode) && $resultCode != 0 ){
                \Drupal::logger('htaccess_insert')->error(implode(', ', $output));
            }else{
                return true;
            }
        } catch (Exception $e) {
            \Drupal::logger('htaccess_insert')->error('Exception: ',  $e->getMessage());
        }
        return false;
    }


}