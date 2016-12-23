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

		$root = new DotNode('root', [DotNode::OPT_LABEL => '""', 'shape' => 'circle']);
		$dotNodes = $this->convert($node);
		foreach ($dotNodes as $dot) {
			$root->addChild($dot);
		}

		$out = "digraph G {\n";
		$out .= "    ";
		$out .= str_replace("\n", "\n    ", $root->getNodes());
		$out .= "\n    ";
		$out .= str_replace("\n", "\n    ", $root->getRelations());
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

		$dot->setOption(DotNode::OPT_SUBLABEL, $this->getSublabel($node));

		foreach ($node->getSubNodeNames() as $key) {
			$value = $node->$key;

			if ($value instanceof Node) {
				$dot->addChild($this->convertSingle($value));

			} elseif (is_array($value)) {
				foreach ($value as $v) {
					if ($v instanceof Node) {
						$dot->addChild($this->convertSingle($v));
					}
				}
			}
		}

		return $dot;
	}


	private function getSublabel(Node $node): ?string
	{
		if ($node instanceof Node\Name) {
			return implode('\\', $node->parts);

		} elseif ($node instanceof Node\Stmt\Function_) {
			return $node->name;

		} elseif ($node instanceof Node\Param) {
			return trim($node->type . ' ' . $node->name);

		} elseif ($node instanceof Node\Expr\Variable) {
			return $node->name;

		} elseif ($node instanceof Node\Scalar) {
			if ($node instanceof Node\Scalar\LNumber
			|| $node instanceof Node\Scalar\String_) {
				return (string) $node->value;
			}

			if ($node instanceof Node\Scalar\MagicConst) {
				return $node->getName();
			}
		}

		return NULL;
	}

}
