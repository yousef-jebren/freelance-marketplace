<?php
class Service {
    private $service_id;
    private $title;
    private $price;
    private $delivery_time;
    private $revisions_included;
    private $freelancer_id;
    private $freelancer_name;
    private $category;
    private $subcategory;
    private $description;
    private $image_1;
    private $added_timestamp;
    
    public function __construct($data) {
        $this->service_id = $data['service_id'];
        $this->title = $data['title'];
        $this->price = $data['price'];
        $this->delivery_time = $data['delivery_time'];
        $this->revisions_included = $data['revisions_included'];
        $this->freelancer_id = $data['freelancer_id'];
        $this->freelancer_name = $data['freelancer_name'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->subcategory = $data['subcategory'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->image_1 = $data['image_1'] ?? '';
        $this->added_timestamp = $data['added_timestamp'] ?? '';
    }
    
    // Getters
    public function getServiceId() {
        return $this->service_id;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function getPrice() {
        return $this->price;
    }
    
    public function getDeliveryTime() {
        return $this->delivery_time;
    }
    
    public function getRevisionsIncluded() {
        return $this->revisions_included;
    }
    
    public function getFreelancerId() {
        return $this->freelancer_id;
    }
    
    public function getFreelancerName() {
        return $this->freelancer_name;
    }
    
    public function getCategory() {
        return $this->category;
    }
    
    public function getSubcategory() {
        return $this->subcategory;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getImage1() {
        return $this->image_1;
    }
    
    public function getAddedTimestamp() {
        return $this->added_timestamp;
    }
    
    // Formatted output methods
    public function getFormattedPrice() {
        return '$' . number_format($this->price, 2);
    }
    
    public function getFormattedDelivery() {
        return $this->delivery_time . ' day' . ($this->delivery_time != 1 ? 's' : '');
    }
    
    public function calculateServiceFee() {
        return $this->price * 0.05;
    }
    
    public function getTotalWithFee() {
        return $this->price + $this->calculateServiceFee();
    }
    
    public function getFormattedServiceFee() {
        return '$' . number_format($this->calculateServiceFee(), 2);
    }
    
    public function getFormattedTotal() {
        return '$' . number_format($this->getTotalWithFee(), 2);
    }
}
?>