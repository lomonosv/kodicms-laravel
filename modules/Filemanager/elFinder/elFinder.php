<?php namespace KodiCMS\Filemanager\elFinder;

class elFinder
{

	/**
	 * API version number
	 *
	 * @var string
	 **/
	protected $version = '2.0';

	/**
	 * Storages (root dirs)
	 *
	 * @var array
	 **/
	protected $volumes = [];

	public static $netDrivers = [];

	/**
	 * Mounted volumes count
	 * Required to create unique volume id
	 *
	 * @var int
	 **/
	public static $volumesCnt = 1;

	/**
	 * Default root (storage)
	 *
	 * @var elFinderStorageDriver
	 **/
	protected $default = NULL;

	/**
	 * Commands and required arguments list
	 *
	 * @var array
	 **/
	protected $commands = [
		'open' => ['target' => FALSE, 'tree' => FALSE, 'init' => FALSE, 'mimes' => FALSE],
		'ls' => ['target' => TRUE, 'mimes' => FALSE],
		'tree' => ['target' => TRUE],
		'parents' => ['target' => TRUE],
		'tmb' => ['targets' => TRUE],
		'file' => ['target' => TRUE, 'download' => FALSE],
		'size' => ['targets' => TRUE],
		'mkdir' => ['target' => TRUE, 'name' => TRUE],
		'mkfile' => ['target' => TRUE, 'name' => TRUE, 'mimes' => FALSE],
		'rm' => ['targets' => TRUE],
		'rename' => ['target' => TRUE, 'name' => TRUE, 'mimes' => FALSE],
		'duplicate' => ['targets' => TRUE, 'suffix' => FALSE],
		'paste' => ['dst' => TRUE, 'targets' => TRUE, 'cut' => FALSE, 'mimes' => FALSE],
		'upload' => ['target' => TRUE, 'FILES' => TRUE, 'mimes' => FALSE, 'html' => FALSE],
		'get' => ['target' => TRUE],
		'put' => ['target' => TRUE, 'content' => '', 'mimes' => FALSE],
		'archive' => ['targets' => TRUE, 'type' => TRUE, 'mimes' => FALSE],
		'extract' => ['target' => TRUE, 'mimes' => FALSE],
		'search' => ['q' => TRUE, 'mimes' => FALSE],
		'info' => ['targets' => TRUE],
		'dim' => ['target' => TRUE],
		'resize' => ['target' => TRUE, 'width' => TRUE, 'height' => TRUE, 'mode' => FALSE, 'x' => FALSE, 'y' => FALSE, 'degree' => FALSE],
		'netmount' => ['protocol' => TRUE, 'host' => TRUE, 'path' => FALSE, 'port' => FALSE, 'user' => TRUE, 'pass' => TRUE, 'alias' => FALSE, 'options' => FALSE]
	];

	/**
	 * Commands listeners
	 *
	 * @var array
	 **/
	protected $listeners = [];

	/**
	 * script work time for debug
	 *
	 * @var string
	 **/
	protected $time = 0;
	/**
	 * Is elFinder init correctly?
	 *
	 * @var bool
	 **/
	protected $loaded = FALSE;
	/**
	 * Send debug to client?
	 *
	 * @var string
	 **/
	protected $debug = FALSE;

	/**
	 * session expires timeout
	 *
	 * @var int
	 **/
	protected $timeout = 0;

	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	protected $uploadDebug = '';

	/**
	 * Errors from not mounted volumes
	 *
	 * @var array
	 **/
	public $mountErrors = [];

	// Errors messages
	const ERROR_UNKNOWN           = 'errUnknown';
	const ERROR_UNKNOWN_CMD       = 'errUnknownCmd';
	const ERROR_CONF              = 'errConf';
	const ERROR_CONF_NO_JSON      = 'errJSON';
	const ERROR_CONF_NO_VOL       = 'errNoVolumes';
	const ERROR_INV_PARAMS        = 'errCmdParams';
	const ERROR_OPEN              = 'errOpen';
	const ERROR_DIR_NOT_FOUND     = 'errFolderNotFound';
	const ERROR_FILE_NOT_FOUND    = 'errFileNotFound';     // 'File not found.'
	const ERROR_TRGDIR_NOT_FOUND  = 'errTrgFolderNotFound'; // 'Target folder "$1" not found.'
	const ERROR_NOT_DIR           = 'errNotFolder';
	const ERROR_NOT_FILE          = 'errNotFile';
	const ERROR_PERM_DENIED       = 'errPerm';
	const ERROR_LOCKED            = 'errLocked';        // '"$1" is locked and can not be renamed, moved or removed.'
	const ERROR_EXISTS            = 'errExists';        // 'File named "$1" already exists.'
	const ERROR_INVALID_NAME      = 'errInvName';       // 'Invalid file name.'
	const ERROR_MKDIR             = 'errMkdir';
	const ERROR_MKFILE            = 'errMkfile';
	const ERROR_RENAME            = 'errRename';
	const ERROR_COPY              = 'errCopy';
	const ERROR_MOVE              = 'errMove';
	const ERROR_COPY_FROM         = 'errCopyFrom';
	const ERROR_COPY_TO           = 'errCopyTo';
	const ERROR_COPY_ITSELF       = 'errCopyInItself';
	const ERROR_REPLACE           = 'errReplace';          // 'Unable to replace "$1".'
	const ERROR_RM                = 'errRm';               // 'Unable to remove "$1".'
	const ERROR_RM_SRC            = 'errRmSrc';            // 'Unable remove source file(s)'
	const ERROR_UPLOAD            = 'errUpload';           // 'Upload error.'
	const ERROR_UPLOAD_FILE       = 'errUploadFile';       // 'Unable to upload "$1".'
	const ERROR_UPLOAD_NO_FILES   = 'errUploadNoFiles';    // 'No files found for upload.'
	const ERROR_UPLOAD_TOTAL_SIZE = 'errUploadTotalSize';  // 'Data exceeds the maximum allowed size.'
	const ERROR_UPLOAD_FILE_SIZE  = 'errUploadFileSize';   // 'File exceeds maximum allowed size.'
	const ERROR_UPLOAD_FILE_MIME  = 'errUploadMime';       // 'File type not allowed.'
	const ERROR_UPLOAD_TRANSFER   = 'errUploadTransfer';   // '"$1" transfer error.'
	// const ERROR_ACCESS_DENIED     = 'errAccess';
	const ERROR_NOT_REPLACE        = 'errNotReplace';       // Object "$1" already exists at this location and can not be replaced with object of another type.
	const ERROR_SAVE               = 'errSave';
	const ERROR_EXTRACT            = 'errExtract';
	const ERROR_ARCHIVE            = 'errArchive';
	const ERROR_NOT_ARCHIVE        = 'errNoArchive';
	const ERROR_ARCHIVE_TYPE       = 'errArcType';
	const ERROR_ARC_SYMLINKS       = 'errArcSymlinks';
	const ERROR_ARC_MAXSIZE        = 'errArcMaxSize';
	const ERROR_RESIZE             = 'errResize';
	const ERROR_UNSUPPORT_TYPE     = 'errUsupportType';
	const ERROR_NOT_UTF8_CONTENT   = 'errNotUTF8Content';
	const ERROR_NETMOUNT           = 'errNetMount';
	const ERROR_NETMOUNT_NO_DRIVER = 'errNetMountNoDriver';
	const ERROR_NETMOUNT_FAILED    = 'errNetMountFailed';

	const ERROR_SESSION_EXPIRES = 'errSessionExpires';

	const ERROR_CREATING_TEMP_DIR = 'errCreatingTempDir';
	const ERROR_FTP_DOWNLOAD_FILE = 'errFtpDownloadFile';
	const ERROR_FTP_UPLOAD_FILE   = 'errFtpUploadFile';
	const ERROR_FTP_MKDIR         = 'errFtpMkdir';
	const ERROR_ARCHIVE_EXEC      = 'errArchiveExec';
	const ERROR_EXTRACT_EXEC      = 'errExtractExec';

	/**
	 * Constructor
	 *
	 * @param  array $opts elFinder and roots configurations
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct($opts)
	{
		if (session_id() == '') {
			session_start();
		}

		$this->time = $this->utime();
		$this->debug = (isset($opts['debug']) && $opts['debug'] ? TRUE : FALSE);
		$this->timeout = (isset($opts['timeout']) ? $opts['timeout'] : 0);

		setlocale(LC_ALL, !empty($opts['locale']) ? $opts['locale'] : 'en_US.UTF-8');

		// bind events listeners
		if (!empty($opts['bind']) && is_array($opts['bind'])) {
			foreach ($opts['bind'] as $cmd => $handler) {
				$this->bind($cmd, $handler);
			}
		}

		if (!isset($opts['roots']) || !is_array($opts['roots'])) {
			$opts['roots'] = [];
		}

		// check for net volumes stored in session
		foreach ($this->getNetVolumes() as $root) {
			$opts['roots'][] = $root;
		}

		// "mount" volumes
		foreach ($opts['roots'] as $i => $o) {
			$class = (isset($o['driver']) ? $o['driver'] : '');

			if (class_exists($class)) {
				$volume = new $class();

				if ($volume->mount($o)) {
					// unique volume id (ends on "_") - used as prefix to files hash
					$id = $volume->id();

					$this->volumes[$id] = $volume;
					if (!$this->default && $volume->isReadable()) {
						$this->default = $this->volumes[$id];
					}
				} else {
					$this->mountErrors[] = 'Driver "' . $class . '" : ' . implode(' ', $volume->error());
				}
			} else {
				$this->mountErrors[] = 'Driver "' . $class . '" does not exists';
			}
		}

		// if at least one redable volume - ii desu >_<
		$this->loaded = !empty($this->default);
	}

	/**
	 * Return true if fm init correctly
	 *
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function loaded()
	{
		return $this->loaded;
	}

	/**
	 * Return version (api) number
	 *
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	public function version()
	{
		return $this->version;
	}

	/**
	 * Add handler to elFinder command
	 *
	 * @param  string  command name
	 * @param  string|array callback name or array(object, method)
	 * @return elFinder
	 * @author Dmitry (dio) Levashov
	 **/
	public function bind($cmd, $handler)
	{
		$cmds = $cmd == '*'
			? array_keys($this->commands)
			: array_map('trim', explode(' ', $cmd));

		foreach ($cmds as $cmd) {
			if ($cmd) {
				if (!isset($this->listeners[$cmd])) {
					$this->listeners[$cmd] = [];
				}

				if (is_callable($handler)) {
					$this->listeners[$cmd][] = $handler;
				}
			}
		}

		return $this;
	}

	/**
	 * Remove event (command exec) handler
	 *
	 * @param  string  command name
	 * @param  string|array callback name or array(object, method)
	 * @return elFinder
	 * @author Dmitry (dio) Levashov
	 **/
	public function unbind($cmd, $handler)
	{
		if (!empty($this->listeners[$cmd])) {
			foreach ($this->listeners[$cmd] as $i => $h) {
				if ($h === $handler) {
					unset($this->listeners[$cmd][$i]);

					return $this;
				}
			}
		}

		return $this;
	}

	/**
	 * Return true if command exists
	 *
	 * @param  string  command name
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	public function commandExists($cmd)
	{
		return $this->loaded && isset($this->commands[$cmd]) && method_exists($this, $cmd);
	}

	/**
	 * Return root - file's owner (public func of volume())
	 *
	 * @param  string  file hash
	 * @return elFinderStorageDriver
	 * @author Naoki Sawada
	 */
	public function getVolume($hash)
	{
		return $this->volume($hash);
	}

	/**
	 * Return command required arguments info
	 *
	 * @param  string  command name
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function commandArgsList($cmd)
	{
		return $this->commandExists($cmd) ? $this->commands[$cmd] : [];
	}

	private function session_expires()
	{

		if (!isset($_SESSION['LAST_ACTIVITY'])) {
			$_SESSION['LAST_ACTIVITY'] = time();

			return FALSE;
		}

		if (($this->timeout > 0) && (time() - $_SESSION['LAST_ACTIVITY'] > $this->timeout)) {
			return TRUE;
		}

		$_SESSION['LAST_ACTIVITY'] = time();

		return FALSE;
	}

	/**
	 * Exec command and return result
	 *
	 * @param  string $cmd command name
	 * @param  array $args command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function exec($cmd, $args)
	{

		if (!$this->loaded) {
			return ['error' => $this->error(self::ERROR_CONF, self::ERROR_CONF_NO_VOL)];
		}

		if ($this->session_expires()) {
			return ['error' => $this->error(self::ERROR_SESSION_EXPIRES)];
		}

		if (!$this->commandExists($cmd)) {
			return ['error' => $this->error(self::ERROR_UNKNOWN_CMD)];
		}

		if (!empty($args['mimes']) && is_array($args['mimes'])) {
			foreach ($this->volumes as $id => $v) {
				$this->volumes[$id]->setMimesFilter($args['mimes']);
			}
		}

		$result = $this->$cmd($args);

		if (isset($result['removed'])) {
			foreach ($this->volumes as $volume) {
				$result['removed'] = array_merge($result['removed'], $volume->removed());
				$volume->resetRemoved();
			}
		}

		// call handlers for this command
		if (!empty($this->listeners[$cmd])) {
			foreach ($this->listeners[$cmd] as $handler) {
				if (call_user_func_array($handler, [$cmd, &$result, $args, $this])) {
					// handler return true to force sync client after command completed
					$result['sync'] = TRUE;
				}
			}
		}

		// replace removed files info with removed files hashes
		if (!empty($result['removed'])) {
			$removed = [];
			foreach ($result['removed'] as $file) {
				$removed[] = $file['hash'];
			}
			$result['removed'] = array_unique($removed);
		}
		// remove hidden files and filter files by mimetypes
		if (!empty($result['added'])) {
			$result['added'] = $this->filter($result['added']);
		}
		// remove hidden files and filter files by mimetypes
		if (!empty($result['changed'])) {
			$result['changed'] = $this->filter($result['changed']);
		}

		if ($this->debug || !empty($args['debug'])) {
			$result['debug'] = [
				'connector' => 'php',
				'phpver' => PHP_VERSION,
				'time' => $this->utime() - $this->time,
				'memory' => (function_exists('memory_get_peak_usage') ? ceil(memory_get_peak_usage() / 1024) . 'Kb / ' : '') . ceil(memory_get_usage() / 1024) . 'Kb / ' . ini_get('memory_limit'),
				'upload' => $this->uploadDebug,
				'volumes' => [],
				'mountErrors' => $this->mountErrors
			];

			foreach ($this->volumes as $id => $volume) {
				$result['debug']['volumes'][] = $volume->debug();
			}
		}

		foreach ($this->volumes as $volume) {
			$volume->umount();
		}

		return $result;
	}

	/**
	 * Return file real path
	 *
	 * @param  string $hash file hash
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	public function realpath($hash)
	{
		if (($volume = $this->volume($hash)) == FALSE) {
			return FALSE;
		}

		return $volume->realpath($hash);
	}

	/**
	 * Return network volumes config.
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 */
	protected function getNetVolumes()
	{
		return isset($_SESSION['elFinderNetVolumes']) && is_array($_SESSION['elFinderNetVolumes']) ? $_SESSION['elFinderNetVolumes'] : [];
	}

	/**
	 * Save network volumes config.
	 *
	 * @param  array $volumes volumes config
	 * @return void
	 * @author Dmitry (dio) Levashov
	 */
	protected function saveNetVolumes($volumes)
	{
		$_SESSION['elFinderNetVolumes'] = $volumes;
	}

	/***************************************************************************/
	/*                                 commands                                */
	/***************************************************************************/

	/**
	 * Normalize error messages
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	public function error()
	{
		$errors = [];

		foreach (func_get_args() as $msg) {
			if (is_array($msg)) {
				$errors = array_merge($errors, $msg);
			} else {
				$errors[] = $msg;
			}
		}

		return count($errors) ? $errors : [self::ERROR_UNKNOWN];
	}

	protected function netmount($args)
	{
		$options = [];
		$protocol = $args['protocol'];
		$driver = isset(self::$netDrivers[$protocol]) ? $protocol : '';
		$class = 'elfindervolume' . $protocol;

		if (!$driver) {
			return ['error' => $this->error(self::ERROR_NETMOUNT, $args['host'], self::ERROR_NETMOUNT_NO_DRIVER)];
		}

		if (!$args['path']) {
			$args['path'] = '/';
		}

		foreach ($args as $k => $v) {
			if ($k != 'options' && $k != 'protocol' && $v) {
				$options[$k] = $v;
			}
		}

		if (is_array($args['options'])) {
			foreach ($args['options'] as $key => $value) {
				$options[$key] = $value;
			}
		}

		$volume = new $class();

		if ($volume->mount($options)) {
			$netVolumes = $this->getNetVolumes();
			$options['driver'] = $driver;
			$netVolumes[] = $options;
			$netVolumes = array_unique($netVolumes);
			$this->saveNetVolumes($netVolumes);

			return ['sync' => TRUE];
		} else {
			return ['error' => $this->error(self::ERROR_NETMOUNT, $args['host'], implode(' ', $volume->error()))];
		}

	}

	/**
	 * "Open" directory
	 * Return array with following elements
	 *  - cwd          - opened dir info
	 *  - files        - opened dir content [and dirs tree if $args[tree]]
	 *  - api          - api version (if $args[init])
	 *  - uplMaxSize   - if $args[init]
	 *  - error        - on failed
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function open($args)
	{
		$target = $args['target'];
		$init = !empty($args['init']);
		$tree = !empty($args['tree']);
		$volume = $this->volume($target);
		$cwd = $volume ? $volume->dir($target, TRUE) : FALSE;
		$hash = $init ? 'default folder' : '#' . $target;

		// on init request we can get invalid dir hash -
		// dir which can not be opened now, but remembered by client,
		// so open default dir
		if ((!$cwd || !$cwd['read']) && $init) {
			$volume = $this->default;
			$cwd = $volume->dir($volume->defaultPath(), TRUE);
		}

		if (!$cwd) {
			return ['error' => $this->error(self::ERROR_OPEN, $hash, self::ERROR_DIR_NOT_FOUND)];
		}
		if (!$cwd['read']) {
			return ['error' => $this->error(self::ERROR_OPEN, $hash, self::ERROR_PERM_DENIED)];
		}

		$files = [];

		// get folders trees
		if ($args['tree']) {
			foreach ($this->volumes as $id => $v) {
				if (($tree = $v->tree('', 0, $cwd['hash'])) != FALSE) {
					$files = array_merge($files, $tree);
				}
			}
		}

		// get current working directory files list and add to $files if not exists in it
		if (($ls = $volume->scandir($cwd['hash'])) === FALSE) {
			return ['error' => $this->error(self::ERROR_OPEN, $cwd['name'], $volume->error())];
		}

		foreach ($ls as $file) {
			if (!in_array($file, $files)) {
				$files[] = $file;
			}
		}

		$result = [
			'cwd' => $cwd,
			'options' => $volume->options($cwd['hash']),
			'files' => $files
		];

		if (!empty($args['init'])) {
			$result['api'] = $this->version;
			$result['uplMaxSize'] = ini_get('upload_max_filesize');
			$result['netDrivers'] = array_keys(self::$netDrivers);
		}

		return $result;
	}

	/**
	 * Return dir files names list
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function ls($args)
	{
		$target = $args['target'];

		if (($volume = $this->volume($target)) == FALSE
			|| ($list = $volume->ls($target)) === FALSE
		) {
			return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
		}

		return ['list' => $list];
	}

	/**
	 * Return subdirs for required directory
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function tree($args)
	{
		$target = $args['target'];

		if (($volume = $this->volume($target)) == FALSE
			|| ($tree = $volume->tree($target)) == FALSE
		) {
			return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
		}

		return ['tree' => $tree];
	}

	/**
	 * Return parents dir for required directory
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function parents($args)
	{
		$target = $args['target'];

		if (($volume = $this->volume($target)) == FALSE
			|| ($tree = $volume->parents($target)) == FALSE
		) {
			return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
		}

		return ['tree' => $tree];
	}

	/**
	 * Return new created thumbnails list
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function tmb($args)
	{

		$result = ['images' => []];
		$targets = $args['targets'];

		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) != FALSE
				&& (($tmb = $volume->tmb($target)) != FALSE)
			) {
				$result['images'][$target] = $tmb;
			}
		}

		return $result;
	}

	/**
	 * Required to output file in browser when volume URL is not set
	 * Return array contains opened file pointer, root itself and required headers
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function file($args)
	{
		$target = $args['target'];
		$download = !empty($args['download']);
		$h403 = 'HTTP/1.x 403 Access Denied';
		$h404 = 'HTTP/1.x 404 Not Found';

		if (($volume = $this->volume($target)) == FALSE) {
			return ['error' => 'File not found', 'header' => $h404, 'raw' => TRUE];
		}

		if (($file = $volume->file($target)) == FALSE) {
			return ['error' => 'File not found', 'header' => $h404, 'raw' => TRUE];
		}

		if (!$file['read']) {
			return ['error' => 'Access denied', 'header' => $h403, 'raw' => TRUE];
		}

		if (($fp = $volume->open($target)) == FALSE) {
			return ['error' => 'File not found', 'header' => $h404, 'raw' => TRUE];
		}

		if ($download) {
			$disp = 'attachment';
			$mime = 'application/octet-stream';
		} else {
			$disp = preg_match('/^(image|text)/i', $file['mime']) || $file['mime'] == 'application/x-shockwave-flash'
				? 'inline'
				: 'attachment';
			$mime = $file['mime'];
		}

		$filenameEncoded = rawurlencode($file['name']);
		if (strpos($filenameEncoded, '%') === FALSE) { // ASCII only
			$filename = 'filename="' . $file['name'] . '"';
		} else {
			$ua = $_SERVER["HTTP_USER_AGENT"];
			if (preg_match('/MSIE [4-8]/', $ua)) { // IE < 9 do not support RFC 6266 (RFC 2231/RFC 5987)
				$filename = 'filename="' . $filenameEncoded . '"';
			} elseif (strpos($ua, 'Chrome') === FALSE && strpos($ua, 'Safari') !== FALSE) { // Safari
				$filename = 'filename="' . str_replace('"', '', $file['name']) . '"';
			} else { // RFC 6266 (RFC 2231/RFC 5987)
				$filename = 'filename*=UTF-8\'\'' . $filenameEncoded;
			}
		}

		$result = [
			'volume' => $volume,
			'pointer' => $fp,
			'info' => $file,
			'header' => [
				'Content-Type: ' . $mime,
				'Content-Disposition: ' . $disp . '; ' . $filename,
				'Content-Location: ' . $file['name'],
				'Content-Transfer-Encoding: binary',
				'Content-Length: ' . $file['size'],
				'Connection: close'
			]
		];

		return $result;
	}

	/**
	 * Count total files size
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function size($args)
	{
		$size = 0;

		foreach ($args['targets'] as $target) {
			if (($volume = $this->volume($target)) == FALSE
				|| ($file = $volume->file($target)) == FALSE
				|| !$file['read']
			) {
				return ['error' => $this->error(self::ERROR_OPEN, '#' . $target)];
			}

			$size += $volume->size($target);
		}

		return ['size' => $size];
	}

	/**
	 * Create directory
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function mkdir($args)
	{
		$target = $args['target'];
		$name = $args['name'];

		if (($volume = $this->volume($target)) == FALSE) {
			return ['error' => $this->error(self::ERROR_MKDIR, $name, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target)];
		}

		return ($dir = $volume->mkdir($target, $name)) == FALSE
			? ['error' => $this->error(self::ERROR_MKDIR, $name, $volume->error())]
			: ['added' => [$dir]];
	}

	/**
	 * Create empty file
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function mkfile($args)
	{
		$target = $args['target'];
		$name = $args['name'];

		if (($volume = $this->volume($target)) == FALSE) {
			return ['error' => $this->error(self::ERROR_MKFILE, $name, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target)];
		}

		return ($file = $volume->mkfile($target, $args['name'])) == FALSE
			? ['error' => $this->error(self::ERROR_MKFILE, $name, $volume->error())]
			: ['added' => [$file]];
	}

	/**
	 * Rename file
	 *
	 * @param  array $args
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function rename($args)
	{
		$target = $args['target'];
		$name = $args['name'];

		if (($volume = $this->volume($target)) == FALSE
			|| ($rm = $volume->file($target)) == FALSE
		) {
			return ['error' => $this->error(self::ERROR_RENAME, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
		}
		$rm['realpath'] = $volume->realpath($target);

		return ($file = $volume->rename($target, $name)) == FALSE
			? ['error' => $this->error(self::ERROR_RENAME, $rm['name'], $volume->error())]
			: ['added' => [$file], 'removed' => [$rm]];
	}

	/**
	 * Duplicate file - create copy with "copy %d" suffix
	 *
	 * @param array $args command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function duplicate($args)
	{
		$targets = is_array($args['targets']) ? $args['targets'] : [];
		$result = ['added' => []];
		$suffix = empty($args['suffix']) ? 'copy' : $args['suffix'];

		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) == FALSE
				|| ($src = $volume->file($target)) == FALSE
			) {
				$result['warning'] = $this->error(self::ERROR_COPY, '#' . $target, self::ERROR_FILE_NOT_FOUND);
				break;
			}

			if (($file = $volume->duplicate($target, $suffix)) == FALSE) {
				$result['warning'] = $this->error($volume->error());
				break;
			}

			$result['added'][] = $file;
		}

		return $result;
	}

	/**
	 * Remove dirs/files
	 *
	 * @param array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function rm($args)
	{
		$targets = is_array($args['targets']) ? $args['targets'] : [];
		$result = ['removed' => []];

		foreach ($targets as $target) {
			if (($volume = $this->volume($target)) == FALSE) {
				$result['warning'] = $this->error(self::ERROR_RM, '#' . $target, self::ERROR_FILE_NOT_FOUND);

				return $result;
			}
			if (!$volume->rm($target)) {
				$result['warning'] = $this->error($volume->error());

				return $result;
			}
		}

		return $result;
	}

	/**
	 * Save uploaded files
	 *
	 * @param  array
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function upload($args)
	{
		$target = $args['target'];
		$volume = $this->volume($target);
		$files = isset($args['FILES']['upload']) && is_array($args['FILES']['upload']) ? $args['FILES']['upload'] : [];
		$result = ['added' => [], 'header' => empty($args['html']) ? FALSE : 'Content-Type: text/html; charset=utf-8'];

		if (empty($files)) {
			return ['error' => $this->error(self::ERROR_UPLOAD, self::ERROR_UPLOAD_NO_FILES), 'header' => $header];
		}

		if (!$volume) {
			return ['error' => $this->error(self::ERROR_UPLOAD, self::ERROR_TRGDIR_NOT_FOUND, '#' . $target), 'header' => $header];
		}

		foreach ($files['name'] as $i => $name) {
			if (($error = $files['error'][$i]) > 0) {
				$result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $name, $error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE ? self::ERROR_UPLOAD_FILE_SIZE : self::ERROR_UPLOAD_TRANSFER);
				$this->uploadDebug = 'Upload error code: ' . $error;
				break;
			}

			$tmpname = $files['tmp_name'][$i];

			if (($fp = fopen($tmpname, 'rb')) == FALSE) {
				$result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $name, self::ERROR_UPLOAD_TRANSFER);
				$this->uploadDebug = 'Upload error: unable open tmp file';
				break;
			}

			if (($file = $volume->upload($fp, $target, $name, $tmpname)) === FALSE) {
				$result['warning'] = $this->error(self::ERROR_UPLOAD_FILE, $name, $volume->error());
				fclose($fp);
				break;
			}

			fclose($fp);
			$result['added'][] = $file;
		}

		return $result;
	}

	/**
	 * Copy/move files into new destination
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function paste($args)
	{
		$dst = $args['dst'];
		$targets = is_array($args['targets']) ? $args['targets'] : [];
		$cut = !empty($args['cut']);
		$error = $cut ? self::ERROR_MOVE : self::ERROR_COPY;
		$result = ['added' => [], 'removed' => []];

		if (($dstVolume = $this->volume($dst)) == FALSE) {
			return ['error' => $this->error($error, '#' . $targets[0], self::ERROR_TRGDIR_NOT_FOUND, '#' . $dst)];
		}

		foreach ($targets as $target) {
			if (($srcVolume = $this->volume($target)) == FALSE) {
				$result['warning'] = $this->error($error, '#' . $target, self::ERROR_FILE_NOT_FOUND);
				break;
			}

			if (($file = $dstVolume->paste($srcVolume, $target, $dst, $cut)) == FALSE) {
				$result['warning'] = $this->error($dstVolume->error());
				break;
			}

			$result['added'][] = $file;
		}

		return $result;
	}

	/**
	 * Return file content
	 *
	 * @param  array $args command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function get($args)
	{
		$target = $args['target'];
		$volume = $this->volume($target);

		if (!$volume || ($file = $volume->file($target)) == FALSE) {
			return ['error' => $this->error(self::ERROR_OPEN, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
		}

		if (($content = $volume->getContents($target)) === FALSE) {
			return ['error' => $this->error(self::ERROR_OPEN, $volume->path($target), $volume->error())];
		}

		$json = json_encode($content);

		if ($json == 'null' && strlen($json) < strlen($content)) {
			return ['error' => $this->error(self::ERROR_NOT_UTF8_CONTENT, $volume->path($target))];
		}

		return ['content' => $content];
	}

	/**
	 * Save content into text file
	 *
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function put($args)
	{
		$target = $args['target'];

		if (($volume = $this->volume($target)) == FALSE
			|| ($file = $volume->file($target)) == FALSE
		) {
			return ['error' => $this->error(self::ERROR_SAVE, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
		}

		if (($file = $volume->putContents($target, $args['content'])) == FALSE) {
			return ['error' => $this->error(self::ERROR_SAVE, $volume->path($target), $volume->error())];
		}

		return ['changed' => [$file]];
	}

	/**
	 * Extract files from archive
	 *
	 * @param  array $args command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function extract($args)
	{
		$target = $args['target'];
		$mimes = !empty($args['mimes']) && is_array($args['mimes']) ? $args['mimes'] : [];
		$error = [self::ERROR_EXTRACT, '#' . $target];

		if (($volume = $this->volume($target)) == FALSE
			|| ($file = $volume->file($target)) == FALSE
		) {
			return ['error' => $this->error(self::ERROR_EXTRACT, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
		}

		return ($file = $volume->extract($target))
			? ['added' => [$file]]
			: ['error' => $this->error(self::ERROR_EXTRACT, $volume->path($target), $volume->error())];
	}

	/**
	 * Create archive
	 *
	 * @param  array $args command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function archive($args)
	{
		$type = $args['type'];
		$targets = isset($args['targets']) && is_array($args['targets']) ? $args['targets'] : [];

		if (($volume = $this->volume($targets[0])) == FALSE) {
			return $this->error(self::ERROR_ARCHIVE, self::ERROR_TRGDIR_NOT_FOUND);
		}

		return ($file = $volume->archive($targets, $args['type']))
			? ['added' => [$file]]
			: ['error' => $this->error(self::ERROR_ARCHIVE, $volume->error())];
	}

	/**
	 * Search files
	 *
	 * @param  array $args command arguments
	 * @return array
	 * @author Dmitry Levashov
	 **/
	protected function search($args)
	{
		$q = trim($args['q']);
		$mimes = !empty($args['mimes']) && is_array($args['mimes']) ? $args['mimes'] : [];
		$result = [];

		foreach ($this->volumes as $volume) {
			$result = array_merge($result, $volume->search($q, $mimes));
		}

		return ['files' => $result];
	}

	/**
	 * Return file info (used by client "places" ui)
	 *
	 * @param  array $args command arguments
	 * @return array
	 * @author Dmitry Levashov
	 **/
	protected function info($args)
	{
		$files = [];

		foreach ($args['targets'] as $hash) {
			if (($volume = $this->volume($hash)) != FALSE
				&& ($info = $volume->file($hash)) != FALSE
			) {
				$files[] = $info;
			}
		}

		return ['files' => $files];
	}

	/**
	 * Return image dimmensions
	 *
	 * @param  array $args command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function dim($args)
	{
		$target = $args['target'];

		if (($volume = $this->volume($target)) != FALSE) {
			$dim = $volume->dimensions($target);

			return $dim ? ['dim' => $dim] : [];
		}

		return [];
	}

	/**
	 * Resize image
	 *
	 * @param  array  command arguments
	 * @return array
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 **/
	protected function resize($args)
	{
		$target = $args['target'];
		$width = $args['width'];
		$height = $args['height'];
		$x = (int)$args['x'];
		$y = (int)$args['y'];
		$mode = $args['mode'];
		$bg = NULL;
		$degree = (int)$args['degree'];

		if (($volume = $this->volume($target)) == FALSE
			|| ($file = $volume->file($target)) == FALSE
		) {
			return ['error' => $this->error(self::ERROR_RESIZE, '#' . $target, self::ERROR_FILE_NOT_FOUND)];
		}

		return ($file = $volume->resize($target, $width, $height, $x, $y, $mode, $bg, $degree))
			? ['changed' => [$file]]
			: ['error' => $this->error(self::ERROR_RESIZE, $volume->path($target), $volume->error())];
	}

	/***************************************************************************/
	/*                                   utils                                 */
	/***************************************************************************/

	/**
	 * Return root - file's owner
	 *
	 * @param  string  file hash
	 * @return elFinderStorageDriver
	 * @author Dmitry (dio) Levashov
	 **/
	protected function volume($hash)
	{
		foreach ($this->volumes as $id => $v) {
			if (strpos('' . $hash, $id) === 0) {
				return $this->volumes[$id];
			}
		}

		return FALSE;
	}

	/**
	 * Return files info array
	 *
	 * @param  array $data one file info or files info
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function toArray($data)
	{
		return isset($data['hash']) || !is_array($data) ? [$data] : $data;
	}

	/**
	 * Return fils hashes list
	 *
	 * @param  array $files files info
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function hashes($files)
	{
		$ret = [];
		foreach ($files as $file) {
			$ret[] = $file['hash'];
		}

		return $ret;
	}

	/**
	 * Remove from files list hidden files and files with required mime types
	 *
	 * @param  array $files files info
	 * @return array
	 * @author Dmitry (dio) Levashov
	 **/
	protected function filter($files)
	{
		foreach ($files as $i => $file) {
			if (!empty($file['hidden']) || !$this->default->mimeAccepted($file['mime'])) {
				unset($files[$i]);
			}
		}

		return array_merge($files, []);
	}

	protected function utime()
	{
		$time = explode(" ", microtime());

		return (double)$time[1] + (double)$time[0];
	}

} // END class