<?php
/**
 *	Asset manager for the PHP Fat-Free Framework
 *
 *	The contents of this file are subject to the terms of the GNU General
 *	Public License Version 3.0. You may not use this file except in
 *	compliance with the license. Any of the license terms and conditions
 *	can be waived if you get permission from the copyright holder.
 *
 *	Copyright (c) 2016 ~ ikkez
 *	Christian Knuth <ikkez0n3@gmail.com>
 *
 *	@version: 1.0.0
 *	@date: 08.11.2016
 *	@since: 08.08.2014
 *
 **/

class Assets extends Prefab {

	/** @var \Base */
	protected $f3;

	/** @var \Template */
	protected $template;

	/** @var array */
	protected $assets;

	/** @var array */
	protected $filter;

	/** @var array */
	protected $formatter;

	public function __construct() {
		$this->template = \Template::instance();
		$f3 = $this->f3 = \Base::instance();
		$opt_defaults = array(
			'auto_include'=>true,
			'greedy'=>false,
			'filter'=>array(),
			'public_path'=>'',
			'combine'=>array(
				'public_path'=>'',
				'exclude'=>'',
				'slots'=>array(
					10=>'top',
					20=>'external',
					40=>'internal',
					60=>'excluded',
					80=>'inline'
				),
			),
			'minify'=>array(
				'public_path'=>'',
				'exclude'=>'.*(.min.).*',
				'inline'=>false,
			),
			'handle_inline'=>false,
			'timestamps'=>false,
			'onFileNotFound'=>null,
			'prepend_base'=>false
		);
		// merge options with defaults
		$f3->set('ASSETS',$f3->exists('ASSETS',$opt) ?
			array_replace_recursive($opt_defaults,$opt) : $opt_defaults);
		// propagate default public temp dir
		if (!$f3->devoid('ASSETS.public_path')) {
			if ($f3->devoid('ASSETS.combine.public_path'))
				$f3->copy('ASSETS.public_path','ASSETS.combine.public_path');
			if ($f3->devoid('ASSETS.minify.public_path'))
				$f3->copy('ASSETS.public_path','ASSETS.minify.public_path');
		}
		$self = $this;
		$this->formatter=array(
			'js'=>function($asset) use($f3,$self){
				if ($asset['origin']=='inline')
					return sprintf('<script>%s</script>',$asset['data']);
				else
					$asset['charset']=$f3->get('ENCODING');
				$path = $asset['path'];
				unset($asset['path'],$asset['origin'],$asset['type'],
					$asset['exclude'],$asset['slot']);
				$params=$self->resolveAttr($asset+array('src'=>$path));
				return sprintf('<script%s></script>',$params);
			},
			'css'=>function($asset) use($f3,$self) {
				if ($asset['origin']=='inline')
					return sprintf('<style type="text/css">%s</style>',$asset['data']);
				$path = $asset['path'];
				unset($asset['path'],$asset['origin'],$asset['type'],
					$asset['exclude'],$asset['slot']);
				$params=$self->resolveAttr($asset+array(
					'rel'=>'stylesheet',
					'type'=>'text/css',
					'href'=>$path,
				));
				return sprintf('<link%s/>',$params);
			}
		);
		$this->filter=array(
			'combine'=>array($this,'combine'),
			'minify'=>array($this,'minify')
		);
		$this->reset();
		if ($f3->get('ASSETS.auto_include')) {
			$this->template->extend('head', 'Assets::renderHeadTag');
			$this->template->extend('body', 'Assets::renderBodyTag');
		}
		$this->template->extend('asset', 'Assets::renderAssetTag');
		if ($f3->get('ASSETS.greedy')) {
			$this->template->extend('script', 'Assets::renderScriptTag');
			$this->template->extend('link', 'Assets::renderLinkCSSTag');
			$this->template->extend('style', 'Assets::renderStyleTag');
		}
		$this->template->afterrender(function($data) use ($f3, $self) {
			foreach($self->getGroups() as $group)
				if (preg_match('<!--\s*assets-'.$group.'+\s*-->',$data))
					$data = preg_replace('/(\s*<!--\s*assets-'.$group.'+\s*-->\s*)/i',
						$self->renderGroup($self->getAssets($group)), $data, 1);
			return $data;
		});
	}

	/**
	 * set custom type formatter
	 * @param string $type
	 * @param $func
	 */
	public function formatter($type,$func) {
		$this->formatter[$type]=$func;
	}

	/**
	 * set custom group filter
	 * @param string $name
	 * @param $func
	 */
	public function filter($name,$func) {
		$this->filter[$name]=$func;
	}

	/**
	 * reset file groups
	 */
	public function reset() {
		$this->assets = array();
	}

	/**
	 * reset the temporary public path
	 * @return integer
	 */
	public function clear() {
		$i=0;
		if ($glob=@glob($this->f3->get('ASSETS.public_path').'*'))
			foreach ($glob as $file)
				if (preg_match('/.+?\.(js|css)/i',basename($file))) {
					$i++;
					@unlink($file);
				}
		return $i;
	}

	/**
	 * get all defined groups
	 * @return array
	 */
	public function getGroups() {
		return array_keys($this->assets);
	}

	/**
	 * get sorted, unique assets from group
	 * @param string $group which group to render
	 * @param string $type which type to render, or all
	 * @return array
	 */
	public function getAssets($group='head',$type=null) {
		$assets = array();
		if (!isset($this->assets[$group]))
			return $assets;
		$types = array_keys($this->assets[$group]);
		foreach($types as $asset_type) {
			if ($type && $type!=$asset_type)
				continue;
			krsort($this->assets[$group][$asset_type]);
			foreach($this->assets[$group][$asset_type] as $prio_set)
				foreach($prio_set as $asset) {
					if ($asset['origin']=='inline')
						$assets[$asset_type][$asset['data']] = $asset;
					else
						$assets[$asset_type][$asset['path']] = $asset;
				}
			$assets[$asset_type] = array_values($assets[$asset_type]);
		}
		return $assets;
	}

	/**
	 * render asset group
	 * @param array $assets
	 * @return string
	 */
	public function renderGroup($assets) {
		$out = array();
		foreach($assets as $asset_type=>$collection) {
			if ($this->f3->exists('ASSETS.filter.'.$asset_type,$filters)) {
				if (is_string($filters))
					$filters = $this->f3->split($filters);
				$flist=array_flip($filters);
				$filters = array_values(array_intersect_key(array_replace($flist, $this->filter), $flist));
				$collection = $this->f3->relay($filters,array($collection));
			}
			foreach($collection as $asset) {
				if (isset($asset['path'])) {
					$path = $asset['path'];
					$mtime = ($this->f3->get('ASSETS.timestamps') && $asset['origin']!='external'
						&& is_file($path)) ? '?'.filemtime($path) : '';
					$base = ($this->f3->get('ASSETS.prepend_base') && $asset['origin']!='external'
						&& is_file($path)) ? $this->f3->get('BASE').'/': '';
					$asset['path'] = $base.$path.$mtime;
				}
				$out[]=$this->f3->call($this->formatter[$asset_type],array($asset));
			}
		}
		return "\n\t".implode("\n\t",$out)."\n";
	}

	/**
	 * combine a whole asset group
	 * @param $collection
	 * @return array
	 */
	public function combine($collection) {
		$public_path = $this->f3->get('ASSETS.combine.public_path');
		if (empty($collection) || count($collection) <= 1)
			return $collection;
		$cfs=$this->f3->get('ASSETS.combine.slots');
		$slots=array_fill_keys(array_keys($cfs),array());
		$sn=array_flip($cfs);
		// sort to slots
		$exclude = $this->f3->get('ASSETS.combine.exclude');
		foreach($collection as $i=>$asset) {
			$a_slot = isset($asset['slot']) ? $asset['slot'] : NULL;
			// auto-create slot
			if ($a_slot && !isset($sn[$a_slot])) {
				$i=50;
				while (isset($slots[$i]))
					$i++;
				$slots[$i]=array();
				$sn[$a_slot]=$i;
			}
			// inline
			if ($asset['origin']=='inline') {
				$slots[$sn[$a_slot?:'inline']][] = $asset;
				continue;
			}
			// external
			if ($asset['origin']=='external') {
				$slots[$sn[$a_slot?:'external']][]=$asset;
			} // internal
			elseif (is_file($asset['path']) && (
				(!isset($asset['exclude']) ||
					!in_array('combine',$this->f3->split($asset['exclude']))) &&
				(empty($exclude) || !preg_match('/'.$exclude.'/i',$asset['path']))) &&
				(!isset($asset['media']) || in_array($asset['media'],array('all','screen')))) {
				$slots[$sn[$a_slot?:'internal']][] = $asset;
			} else
				// excluded internal
				$slots[$sn[$a_slot?:'excluded']][] = $asset;
		}
		// proceed slots
		ksort($slots);
		$out = array();
		foreach ($slots as $assets) {
			$internal=array();
			$inline=array();
			$hash_key=array();
			// categorize per slot
			foreach ($assets as $asset) {
				if ($asset['origin']=='internal') {
					$internal[$asset['type']][] = $asset;
					// check if one of our combined files was changed (mtime)
					if (!isset($hash_key[$asset['type']]))
						$hash_key[$asset['type']]='';
					$hash_key[$asset['type']].=$asset['path'].filemtime($asset['path']);
				}
				elseif ($asset['origin']=='external')
					$out[] = $asset;
				elseif ($asset['origin']=='inline')
					$inline[$asset['type']][] = $asset['data'];
			}
			// combine internals to one file
			if (!empty($internal)) {
				foreach ($internal as $type => $int_a) {
					$filepath = $public_path.$this->f3->hash($hash_key[$type]).'.'.$type;
					if (!is_dir($public_path))
						mkdir($public_path,0777,true);
					$content = array();
					if (!is_file($filepath)) {
						foreach($int_a as $asset) {
							$data = $this->f3->read($asset['path']);
							if ($type=='css')
								$data = $this->fixRelativePaths($data,
									pathinfo($asset['path'],PATHINFO_DIRNAME).'/');
							$content[] = $data;
						}
						$this->f3->write($filepath,
							implode(($type=='js'?';':'')."\n",$content));
					}
					$out[] = array(
						'path'=>$filepath,
						'type'=>$type,
						'origin'=>'internal'
					);
				}
			}
			// combine inline
			if (!empty($inline)) {
				foreach ($inline as $type => $inl_a) {
					$out[] = array(
						'data'=>implode($inl_a),
						'type'=>$type,
						'origin'=>'inline'
					);
				}
			}
		}
		return $out;
	}

	/**
	 * minify each file in a collection
	 * @param $collection
	 * @return mixed
	 */
	public function minify($collection) {
		$web = \Web::instance();
		// check final path
		$public_path = $this->f3->get('ASSETS.minify.public_path');
		if (!is_dir($public_path))
			mkdir($public_path,0777,true);
		$type = false;
		$inline_stack = array();
		foreach($collection as $i=>&$asset) {
			$type = $asset['type'];
			if ($asset['origin']=='inline') {
				$slot = $asset['slot'] ?: 'default';
				$inline_stack[$slot][] = $asset['data'];
				unset($collection[$i]);
				unset($asset);
				continue;
			}
			$path = $asset['path'];
			$exclude = $this->f3->get('ASSETS.minify.exclude');
			// skip external files
			if ($asset['origin'] == 'external')
				continue;
			elseif (is_file($path) && (
				(!isset($asset['exclude']) ||
					!in_array('minify',$this->f3->split($asset['exclude']))) &&
				(empty($exclude) || !preg_match('/'.$exclude.'/i',$path)))) {
				// proceed
				$path_parts = pathinfo($path);
				$filename = $path_parts['filename'].'.min.'.$type;
				if (!is_file($public_path.$filename) ||
					(filemtime($path)>filemtime($public_path.$filename))) {
					$min = $web->minify($path_parts['basename'],null,false,
						$path_parts['dirname'].'/');
					if ($type=='css')
						$min = $this->fixRelativePaths($min,
							$path_parts['dirname'].'/');
					$this->f3->write($public_path.$filename,$min);
				}
				$asset['path'] = $public_path.$filename;
			}
			unset($asset);
		}
		if (!empty($inline_stack)) {
			foreach ($inline_stack as $slotGroup=>$inlineData) {
				$data = implode($inlineData);
				if ($this->f3->get('ASSETS.minify.inline')) {
					// this is probably pretty slow
					$hash = $this->f3->hash($data);
					$filename = $hash.'.min.'.$type;
					if (!is_file($public_path.$filename)) {
						$this->f3->write($public_path.$filename,$data);
						$min = $web->minify($filename,null,false,
							$public_path);
						$this->f3->write($public_path.$filename,$min);
					}
					$collection[] = array(
						'path'=>$public_path.$filename,
						'type'=>$type,
						'origin'=>'internal',
						'slot'=>$slotGroup,
					);
				} else {
					$collection[] = array(
						'data'=>$data,
						'type'=>$type,
						'origin'=>'inline',
						'slot'=>$slotGroup,
					);
				}
			}
		}
		return $collection;
	}

	/**
	 * Rewrite relative URLs in CSS
	 * @author Bong Cosca, from F3 v2.0.13, http://bit.ly/1Mwl7nq
	 * @param string $content
	 * @param string $path
	 * @return string
	 */
	protected function fixRelativePaths($content,$path) {
		// Rewrite relative URLs in CSS
		$f3=$this->f3;
		$base=$f3->get('BASE');
		$out = preg_replace_callback(
			'/\b(?<=url)\((?:([\"\']?)(.+?)((\?.*?)?)\1)\)/s',
			function($url) use($path,$f3,$base) {
				// Ignore absolute URLs
				if (preg_match('/https?:/',$url[2]) ||
					!$rPath=realpath($path.$url[2]))
					return $url[0];
				// absolute to web root / base
				// TODO: maybe build full relative paths?
				return '('.$url[1].preg_replace(
					'/'.preg_quote($f3->fixslashes($_SERVER['DOCUMENT_ROOT']).$base.'/','/').'(.+)/',
					'\1',$base.'/'.$f3->fixslashes($rPath).(isset($url[4])?$url[4]:'')
				).$url[1].')';
			},$content);
		return $out;
	}

	/**
	 * add an asset
	 * @param string $path
	 * @param string $type
	 * @param string $group
	 * @param int $priority
	 * @param string $slot
	 * @param array $params
	 */
	public function add($path,$type,$group='head',$priority=5,$slot=null,$params=null) {
		if (!isset($this->assets[$group]))
			$this->assets[$group]=array();
		if (!isset($this->assets[$group][$type]))
			$this->assets[$group][$type]=array();
		$asset = array(
			'path'=>$path,
			'type'=>$type,
			'slot'=>$slot,
			'origin'=>''
		) + ($params?:array());
		if (preg_match('/^(http(s)?:)?\/\/.*/i',$path)) {
			$asset['origin'] = 'external';
			$this->assets[$group][$type][$priority][]=$asset;
			return;
		}
		foreach ($this->f3->split($this->f3->get('UI')) as $dir)
			if (is_file($view=$this->f3->fixslashes($dir.$path))) {
				$asset['path']=ltrim($view,'./');
				$asset['origin']='internal';
				$this->assets[$group][$type][$priority][]=$asset;
				return;
			}
		// file not found
		if ($handler=$this->f3->get('ASSETS.onFileNotFound'))
			$this->f3->call($handler,array($path,$this));
		$this->assets[$group][$type][$priority][]=$asset;
	}

	/**
	 * add a javascript asset
	 * @param string $path
	 * @param int $priority
	 * @param string $group
	 * @param null $slot
	 */
	public function addJs($path,$priority=5,$group='footer',$slot=null) {
		$this->add($path,'js',$group,$priority,$slot);
	}

	/**
	 * add a css asset
	 * @param string $path
	 * @param int $priority
	 * @param string $group
	 * @param null $slot
	 */
	public function addCss($path,$priority=5,$group='head',$slot=null) {
		$this->add($path,'css',$group,$priority,$slot);
	}

	/**
	 * add inline script or styles
	 * @param string $content
	 * @param string $type
	 * @param string $group
	 * @param string $slot
	 */
	public function addInline($content,$type,$group='head',$slot='inline') {
		if (!isset($this->assets[$group]))
			$this->assets[$group]=array();
		if (!isset($this->assets[$group][$type]))
			$this->assets[$group][$type]=array();
		$this->assets[$group][$type][3][]=array(
			'data'=>$content,
			'type'=>$type,
			'origin'=>'inline',
			'slot'=>$slot,
		);
	}

	/**
	 * push new asset during template execution
	 * @param $node
	 * @return string
	 */
	public function addNode($node) {
		$src=false;
		// find src
		if (array_key_exists('src',$node))
			$src = $node['src'];
		elseif (array_key_exists('href',$node))
			$src = $node['href'];
		if ($src) {
			// find type
			if (!isset($node['type'])) {
				if (preg_match('/.*\.(css|js)(?=[?#].*|$)/i',$src,$match))
					$node['type'] = $match[1];
				elseif (array_key_exists('src',$node))
					$node['type'] = 'js';
				elseif (array_key_exists('href',$node))
					$node['type'] = 'css';
				elseif(empty($type))
					// unknown file type
					return "";
			}
			$type = $node['type'];
			// default slot is based on the type
			if (!isset($node['group']))
				$node['group'] = ($node['type'] == 'js')
					? 'footer' : 'head';
			if (!isset($node['priority']))
				$node['priority'] = 5;
			if (!isset($node['slot']))
				$node['slot'] = null;
			$group = $node['group'];
			$prio = $node['priority'];
			$slot = $node['slot'];
			unset($node['priority'],$node['src'],$node['href'],
				$node['group'],$node['type'],$node['slot']);
			$this->add($src,$type,$group,$prio,$slot,$node);
		}
	}

	/**
	 * parse node data on template compiling
	 * @param $node
	 * @return string
	 */
	function parseNode($node) {
		$src=false;
		$params = array();
		if (isset($node['@attrib'])) {
			$params = $node['@attrib'];
			unset($node['@attrib']);
		}
		// find src
		if (array_key_exists('src',$params))
			$src = $params['src'];
		elseif (array_key_exists('href',$params))
			$src = $params['href'];
		if ($src) {
			$out = '<?php \Assets::instance()->addNode(array(';
			foreach($params as $key=>$val)
				$out.=var_export($key,true).'=>'.(preg_match('/{{(.+?)}}/s',$val)
					?$this->template->token($val):var_export($val,true)).',';
			$out.=')); ?>';
			return $out;
		}
		// inner content
		if (isset($node[0]) && isset($params['type'])) {
			if (!isset($params['group']))
				$params['group'] = ($params['type'] == 'js')
					? 'footer' : 'head';
			if (!isset($params['slot']))
				$params['slot'] = 'inline';
			if ($this->f3->get('ASSETS.handle_inline'))
				return '<?php \Assets::instance()->addInline('.
				'$this->resolve('.(var_export($node,true)).',get_defined_vars()),'.
				var_export($params['type'],true).','.
				var_export($params['group'],true).','.
				var_export($params['slot'],true).'); ?>';
			else
				// just bypass
				return $this->f3->call($this->formatter[$params['type']],array(array(
					'data'=>$this->template->build($node),
					'origin'=>'inline'
				)));
		}
	}

	/**
	 * general bypass for unhandled tag attributes
	 * @param array $attr
	 * @return string
	 */
	function resolveAttr(array $attr) {
		$out = '';
		foreach ($attr as $key => $value) {
			// build dynamic tokens
			if (preg_match('/{{(.+?)}}/s', $value))
				$value = $this->template->build($value);
			if (preg_match('/{{(.+?)}}/s', $key))
				$key = $this->template->build($key);
			// inline token
			if (is_numeric($key))
				$out .= ' '.$value;
			// value-less parameter
			elseif($value==NULL)
				$out .= ' '.$key;
			// key-value parameter
			else
				$out .= ' '.$key.'="'.$value.'"';
		}
		return $out;
	}

	/**
	 * return placeholder for pending afterrender event
	 * @param string $group
	 * @return string
	 */
	public function render($group='head') {
		return '<!-- assets-'.$group.' -->';
	}

	/**
	 * handle <asset> template tag
	 * @param $node
	 * @return mixed
	 */
	static public function renderAssetTag(array $node) {
		// dynamic build on final rendering
		$that = \Assets::instance();
		return $that->parseNode($node);
	}

	/**
	 * crawl <script> tags in greedy mode
	 * @param array $node
	 * @return mixed
	 */
	static public function renderScriptTag(array $node) {
		if (!isset($node['@attrib']))
			$node['@attrib'] = array();
		$node['@attrib']['type']='js';
		return static::renderAssetTag($node);
	}

	/**
	 * crawl <style> tags in greedy mode
	 * @param array $node
	 * @return mixed
	 */
	static public function renderStyleTag(array $node) {
		if (!isset($node['@attrib']))
			$node['@attrib'] = array();
		$node['@attrib']['type']='css';
		return static::renderAssetTag($node);
	}

	/**
	 * crawl <link> tags in greedy mode
	 * @param array $node
	 * @return mixed|string
	 */
	static public function renderLinkCSSTag(array $node) {
		if (isset($node['@attrib']))
			// detect stylesheet link tags
			if ((isset($node['@attrib']['type']) &&
				$node['@attrib']['type'] == 'text/css') ||
				(isset($node['@attrib']['rel']) &&
					$node['@attrib']['rel'] == 'stylesheet')) {
				$node['@attrib']['type']='css';
				return static::renderAssetTag($node);
			} else {
				// skip other other <link> nodes and render them directly
				$as=\Assets::instance();
				$params = '';
				if (isset($node['@attrib'])) {
					$params = $as->resolveAttr($node['@attrib']);
					unset($node['@attrib']);
				}
				return "\t".'<link'.$params.' />';
			}
	}

	/**
	 * handle <head> template tag
	 * @param $node
	 * @return mixed
	 */
	static public function renderHeadTag(array $node) {
		// static build is enough to insert a marker
		$that = \Assets::instance();
		return $that->_head($node);
	}

	/**
	 * auto-append slot marker into <head>
	 * @param $node
	 * @return string
	 */
	public function _head($node) {
		unset($node['@attrib']);
		$content = array();
		// bypass inner content nodes
		foreach ($node as $el)
			$content[] = $this->template->build($el);
		return '<head>'.implode("\n", $content).
			$this->render('head')."\n".'</head>';
	}

	/**
	 * handle <head> template tag
	 * @param $node
	 * @return mixed
	 */
	static public function renderBodyTag(array $node) {
	    // static build is enough to insert a marker
		$that = \Assets::instance();
		return $that->_body($node);
	}

	/**
	 * auto-append footer slot marker into <body>
	 * @param $node
	 * @return string
	 */
	public function _body($node) {
		$params = '';
		if (isset($node['@attrib'])) {
			$params = $this->resolveAttr($node['@attrib']);
			unset($node['@attrib']);
		}
		$content = array();
		// bypass inner content nodes
		foreach ($node as $el)
			$content[] = $this->template->build($el);
		return '<body'.$params.'>'.implode("\n", $content)."\n".
			$this->render('footer')."\n".'</body>';
	}
}
