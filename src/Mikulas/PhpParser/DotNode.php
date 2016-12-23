<?php declare(strict_types = 1);

namespace Mikulas\PhpParser;


class DotNode
{

	const OPT_LABEL = 'label';
	const OPT_SUBLABEL = 'sublabel';


	/** @var string */
	private $id;

	/** @var array */
	private $options;

	/** @var DotNode[] */
	private $children = [];


	public function __construct(string $id, array $options = [])
	{
		$this->id = $id;
		$this->options = $options + [
			'shape' => 'box',
		];
	}


	public function setOption($key, $value): void
	{
		$this->options[$key] = $value;
	}


	private function getAttributes(): array
	{
		$attrs = $this->options;

		$label = $attrs[self::OPT_LABEL];
		if (!empty($attrs[self::OPT_SUBLABEL])) {
			$sub = $attrs[self::OPT_SUBLABEL];
			$attrs[self::OPT_LABEL] = "<$label<BR /><FONT POINT-SIZE=\"10\" FACE=\"Courier\">$sub</FONT>>";
		}
		unset($attrs[self::OPT_SUBLABEL]);

		return $attrs;
	}


	public function getNodes(): string
	{
		$keys = [];
		foreach ($this->getAttributes() as $option => $value) {
			$keys[] = "$option=$value";
		}
		$out = '"' . $this->id . '" [' . implode(' ', $keys) . "];\n";

		foreach ($this->children as $child) {
			$out .= $child->getNodes();
		}
		return $out;
	}


	public function getRelations(): string
	{
		$out = '';
		foreach ($this->children as $child) {
			$out .= "\"$this->id\" -> \"$child->id\"\n";
		}
		foreach ($this->children as $child) {
			$out .= $child->getRelations();
		}
		return $out;
	}


	public function addChild(DotNode $child): void
	{
		$this->children[] = $child;
	}

}
