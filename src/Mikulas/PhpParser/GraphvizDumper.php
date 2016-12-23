<?php declare(strict_types=1);

namespace Mikulas\PhpParser;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;


class GraphvizDumper
{

	/**
	 * @return string Dumped value
	 */
	public function dump($node): string
	{
		assert($node instanceof Node || is_array($node));

		$dotNodes = $this->convert($node);
		$out = "digraph G {\n";
		$out .= "    ";
		foreach ($dotNodes as $dot) {
			$out .= str_replace("\n", "\n    ", $dot->getNodes());
		}
		$out .= "\n    ";
		foreach ($dotNodes as $dot) {
			$out .= str_replace("\n", "\n    ", $dot->getRelations());
		}
		$out .= "\n}\n";
		return $out;
	}


	/**
	 * @param Node[]|Node $node
	 * @return DotNode[]
	 */
	private function convert($node): array
	{
		if ($node instanceof Node) {
			return [$this->convertSingle($node)];

		} elseif (is_array($node)) {
			$dots = [];
			foreach ($node as $n) {
				$dots[] = $this->convertSingle($n);
			}
			return $dots;
		}

		throw new \InvalidArgumentException('Can only dump nodes and arrays.');
	}


	private function convertSingle(Node $node): DotNode
	{
		$dot = new DotNode(spl_object_hash($node), [
			DotNode::OPT_LABEL => $node->getType(),
		]);

		foreach ($node->getSubNodeNames() as $key) {
			$value = $node->$key;

			$sublabel = NULL;
			if (false === $value) {
				$sublabel = 'false';
			} elseif (true === $value) {
				$sublabel = 'true';
			} elseif (is_scalar($value)) {
				if ('flags' === $key || 'newModifier' === $key) {
					$sublabel = $this->dumpFlags($value);
				} else if ('type' === $key && $node instanceof Include_) {
					$sublabel = $this->dumpIncludeType($value);
				} else if ('type' === $key
					&& ($node instanceof Use_ || $node instanceof UseUse || $node instanceof GroupUse)) {
					$sublabel = $this->dumpUseType($value);
				} else {
					$sublabel = $value;
				}
			} else {
				if ($value instanceof Node || is_array($value)) {
					foreach ($this->convert($value) as $child) {
						$dot->addChild($child);
					}
				}

				$sublabel = $this->getSublabel($node);
			}
			$dot->setOption(DotNode::OPT_SUBLABEL, $sublabel);
		}

		return $dot;
	}


	private function getSublabel(Node $node): ?string
	{
		if ($node instanceof Node\Stmt\Function_) {
			return $node->name;
		} elseif ($node instanceof Node\Param) {
			return $node->type . ' ' . $node->name;
		}
		return NULL;
	}


	protected function dumpFlags($flags) {
		$strs = [];
		if ($flags & Class_::MODIFIER_PUBLIC) {
			$strs[] = 'MODIFIER_PUBLIC';
		}
		if ($flags & Class_::MODIFIER_PROTECTED) {
			$strs[] = 'MODIFIER_PROTECTED';
		}
		if ($flags & Class_::MODIFIER_PRIVATE) {
			$strs[] = 'MODIFIER_PRIVATE';
		}
		if ($flags & Class_::MODIFIER_ABSTRACT) {
			$strs[] = 'MODIFIER_ABSTRACT';
		}
		if ($flags & Class_::MODIFIER_STATIC) {
			$strs[] = 'MODIFIER_STATIC';
		}
		if ($flags & Class_::MODIFIER_FINAL) {
			$strs[] = 'MODIFIER_FINAL';
		}

		if ($strs) {
			return implode(' | ', $strs) . ' (' . $flags . ')';
		} else {
			return $flags;
		}
	}

	protected function dumpIncludeType($type) {
		$map = [
			Include_::TYPE_INCLUDE  => 'TYPE_INCLUDE',
			Include_::TYPE_INCLUDE_ONCE => 'TYPE_INCLUDE_ONCE',
			Include_::TYPE_REQUIRE  => 'TYPE_REQUIRE',
			Include_::TYPE_REQUIRE_ONCE => 'TYPE_REQURE_ONCE',
		];

		if (!isset($map[$type])) {
			return $type;
		}
		return $map[$type] . ' (' . $type . ')';
	}

	protected function dumpUseType($type) {
		$map = [
			Use_::TYPE_UNKNOWN  => 'TYPE_UNKNOWN',
			Use_::TYPE_NORMAL   => 'TYPE_NORMAL',
			Use_::TYPE_FUNCTION => 'TYPE_FUNCTION',
			Use_::TYPE_CONSTANT => 'TYPE_CONSTANT',
		];

		if (!isset($map[$type])) {
			return $type;
		}
		return $map[$type] . ' (' . $type . ')';
	}

	// Copied from Error class


	private function toColumn($code, $pos) {
		if ($pos > strlen($code)) {
			throw new \RuntimeException('Invalid position information');
		}

		$lineStartPos = strrpos($code, "\n", $pos - strlen($code));
		if (false === $lineStartPos) {
			$lineStartPos = -1;
		}

		return $pos - $lineStartPos;
	}
}
