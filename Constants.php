<?php
/**
 * This file is part of the Japanese Consumption Tax Extension For Magento2 project.
 *
 * Copyright (c) 2023 Adobe (or other copyright holders)
 *
 * For the full copyright and license information, please view the OSL-3.0
 * license that is bundled with this source code in the file LICENSE, or
 * at https://opensource.org/licenses/OSL-3.0
 */
namespace Magentoj\JapaneseConsumptionTax;

class Constants
{
    public const JCT_10_PERCENT = 10.0;
    public const JCT_8_PERCENT = 8.0;
    public const JCT_0_PERCENT = 0.0;
    
    public const JCT_PERCENTS = [
      self::JCT_10_PERCENT,
      self::JCT_8_PERCENT,
      self::JCT_0_PERCENT,
    ];
}
