<?php

class ShippingService {
    private $shippingConfig;

    public function __construct() {
        $configPath = __DIR__ . '/../shipping-config.json';
        $configJson = file_get_contents($configPath);
        $this->shippingConfig = json_decode($configJson, true);
    }

    /**
     * Calculate shipping charges based on cart total
     * 
     * @param float $cartTotal The total cart amount
     * @return array Array containing shipping charge and details
     */
    public function calculateShippingCharge($cartTotal) {
        $cartTotal = floatval($cartTotal);
        
        foreach ($this->shippingConfig['shipping_charges'] as $range) {
            $min = $range['min'];
            $max = $range['max'];
            $charge = $range['charge'];
            
            // Check if cart total falls within this range
            if ($cartTotal >= $min && ($max === null || $cartTotal <= $max)) {
                return [
                    'success' => true,
                    'cart_total' => $cartTotal,
                    'shipping_charge' => $charge,
                    'final_total' => $cartTotal + $charge,
                    'range' => [
                        'min' => $min,
                        'max' => $max ?? 'unlimited'
                    ]
                ];
            }
        }
        
        // Default fallback (should not reach here if config is complete)
        return [
            'success' => false,
            'error' => 'Unable to calculate shipping charge',
            'cart_total' => $cartTotal
        ];
    }

    /**
     * Get all shipping charge ranges
     * 
     * @return array All shipping charge configurations
     */
    public function getShippingRanges() {
        return $this->shippingConfig['shipping_charges'];
    }

    /**
     * Check if order has the lowest shipping charge
     * 
     * @param float $cartTotal The total cart amount
     * @return bool True if order qualifies for lowest shipping tier
     */
    public function isLowestShippingTier($cartTotal) {
        $result = $this->calculateShippingCharge($cartTotal);
        return $result['success'] && $result['shipping_charge'] <= 50;
    }

    /**
     * Get shipping charge information
     * 
     * @param float $cartTotal Current cart total
     * @return array Information about current shipping charge
     */
    public function getFreeShippingInfo($cartTotal) {
        $result = $this->calculateShippingCharge($cartTotal);
        
        if (!$result['success']) {
            return [
                'qualifies' => false,
                'message' => 'Unable to calculate shipping'
            ];
        }
        
        return [
            'qualifies' => true,
            'current_charge' => $result['shipping_charge'],
            'message' => "Shipping charge: ₹{$result['shipping_charge']}"
        ];
    }
}
