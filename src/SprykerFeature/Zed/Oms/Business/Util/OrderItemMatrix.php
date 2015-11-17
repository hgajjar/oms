<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Oms\Business\Util;

use Orm\Zed\Oms\Persistence\SpyOmsOrderProcess;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use SprykerFeature\Zed\Oms\OmsConfig;
use SprykerFeature\Zed\Oms\Persistence\OmsQueryContainerInterface;
use SprykerFeature\Zed\Sales\Business\SalesFacade;

class OrderItemMatrix
{

    const COL_STATE = 'COL_STATE';

    /**
     * @var OmsQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var OmsConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $processes;

    /**
     * @var SpySalesOrderItem[]
     */
    protected $orderItems;

    /**
     * @var array
     */
    protected $orderItemStates = [];

    /**
     * @var array
     */
    protected $orderItemStateBlacklist = [];

    /**
     * @param OmsQueryContainerInterface $queryContainer
     * @param OmsConfig $config
     */
    public function __construct(OmsQueryContainerInterface $queryContainer, OmsConfig $config)
    {
        $this->queryContainer = $queryContainer;
        $this->config = $config;

        $activeProcesses = $this->getActiveProcesses();

        $processes = [];
        foreach ($activeProcesses as $process) {
            $processes[$process->getIdOmsOrderProcess()] = $process->getName();
        }
        $this->processes = $processes;

        $stateBlacklist = $config->getStateBlacklist();
        $orderItems = $this->queryContainer->queryMatrixOrderItems(array_keys($processes), $stateBlacklist)
            ->find();

        $items = [];
        foreach ($orderItems as $orderItem) {
            $idState = $orderItem->getFkOmsOrderItemState();
            if (!in_array($idState, $this->orderItemStates)) {
                $this->orderItemStates[] = $idState;
            }
            $idProcess = $orderItem->getFkOmsOrderProcess();
            $items[$idState][$idProcess][] = $orderItem;
        }
        $this->orderItems = $items;
    }

    /**
     * @return array
     */
    public function getMatrix()
    {
        $results = [$this->getHeaderColumns()];

        $statesUsed = $this->getOrderItemStateNames($this->orderItemStates);

        foreach ($this->orderItems as $idState => $orderItemsPerProcess) {
            if (!isset($statesUsed[$idState])) {
                continue;
            }

            $result = [
                self::COL_STATE => $statesUsed[$idState]
            ];

            foreach ($this->processes as $idProcess => $process) {
                $element = '';
                if (!empty($orderItemsPerProcess[$idProcess])) {
                    $element = $this->formatElement($orderItemsPerProcess[$idProcess], $idProcess, $idState);
                }

                $result[$process] = $element;
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * @return array
     */
    protected function getHeaderColumns() {
        $headersColumns = [
            self::COL_STATE => ''
        ];
        foreach ($this->processes as $id => $name) {
            $headersColumns[$name] = $name;
        }

        return $headersColumns;
    }

    /**
     * @param SpySalesOrderItem[] $orderItems
     * @param int $idProcess
     * @param int $idState
     *
     * @return string
     */
    protected function formatElement($orderItems, $idProcess, $idState)
    {
        $grid = [
            'day' => 0,
            'week' => 0,
            'other' => 0,
        ];
        foreach ($orderItems as $orderItem) {
            $created = $orderItem->getLastStateChange();

            $lastDay = new \DateTime('-1 day');
            if ($created > $lastDay) {
                ++$grid['day'];
                continue;
            }

            $lastDay = new \DateTime('-7 day');
            if ($created > $lastDay) {
                ++$grid['week'];
                continue;
            }

            ++$grid['other'];
        }

        foreach ($grid as $key => $value) {
            $url = sprintf('/sales?id-order-item-process=%s&id-order-item-process-state=%s&filter=%s', $idProcess, $idState, $key);
            $grid[$key] = '<a href="' . $url . '">' . $value . '</a>';
        }

        return implode(' | ', $grid);
    }

    /**
     * @return SpyOmsOrderProcess[]
     */
    protected function getActiveProcesses()
    {
        return $this->queryContainer
            ->getActiveProcesses($this->config->getActiveProcesses())
            ->find();
    }

    /**
     * @param array $orderItemStates
     * @return array
     */
    protected function getOrderItemStateNames(array $orderItemStates)
    {
        $results = $this->queryContainer->getOrderItemStates($orderItemStates)->find();

        $orderItemStates = [];
        foreach ($results as $result) {
            $orderItemStates[$result->getIdOmsOrderItemState()] = $result->getName();
        }

        return $orderItemStates;
    }

}