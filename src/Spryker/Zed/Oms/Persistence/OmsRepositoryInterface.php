<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Persistence;

use Generated\Shared\Transfer\StoreTransfer;

interface OmsRepositoryInterface
{
    /**
     * @param int[] $processIds
     * @param int[] $stateBlackList
     *
     * @return array
     */
    public function getMatrixOrderItems(array $processIds, array $stateBlackList): array;

    /**
     * @param \Spryker\Zed\Oms\Business\Process\State[] $states
     * @param string $sku
     * @param \Generated\Shared\Transfer\StoreTransfer|null $storeTransfer
     *
     * @return \Generated\Shared\Transfer\ItemTransfer[]
     */
    public function getSalesOrderItemsBySkuAndStatesNames(array $states, string $sku, ?StoreTransfer $storeTransfer): array;
}
