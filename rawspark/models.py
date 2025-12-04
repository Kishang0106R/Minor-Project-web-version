from django.db import models

class User(models.Model):
    name = models.CharField(max_length=50)
    email = models.CharField(max_length=50, unique=True)
    password = models.CharField(max_length=255)
    reg_date = models.DateTimeField(auto_now_add=True)
    phone = models.CharField(max_length=15, blank=True, null=True)
    gender = models.CharField(max_length=10, blank=True, null=True)
    flat_house = models.CharField(max_length=255, blank=True, null=True)
    building_apartment = models.CharField(max_length=255, blank=True, null=True)
    street_road = models.CharField(max_length=255, blank=True, null=True)
    landmark = models.CharField(max_length=255, blank=True, null=True)
    area_locality = models.CharField(max_length=255, blank=True, null=True)
    pincode = models.CharField(max_length=10, blank=True, null=True)
    district = models.CharField(max_length=100, blank=True, null=True)
    city = models.CharField(max_length=100, default='Delhi')
    state = models.CharField(max_length=100, default='Delhi (NCT)')
    points = models.IntegerField(default=0)

    def __str__(self):
        return self.name

class Teacher(models.Model):
    name = models.CharField(max_length=50)
    email = models.CharField(max_length=50, unique=True)
    password = models.CharField(max_length=255)
    subject = models.CharField(max_length=50, blank=True, null=True)
    school_name = models.CharField(max_length=100, blank=True, null=True)
    designation = models.CharField(max_length=50, blank=True, null=True)
    class_assigned = models.CharField(max_length=20, blank=True, null=True)
    mobile = models.CharField(max_length=15, blank=True, null=True)
    reg_date = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return self.name

class Principal(models.Model):
    name = models.CharField(max_length=50)
    email = models.CharField(max_length=50, unique=True)
    password = models.CharField(max_length=255)
    school_name = models.CharField(max_length=100, blank=True, null=True)
    school_code = models.CharField(max_length=50, blank=True, null=True)
    district = models.CharField(max_length=50, blank=True, null=True)
    school_type = models.CharField(max_length=50, blank=True, null=True)
    address = models.TextField(blank=True, null=True)
    mobile = models.CharField(max_length=15, blank=True, null=True)
    reg_date = models.DateTimeField(auto_now_add=True)
    principal_name = models.CharField(max_length=50, blank=True, null=True)
    zone = models.CharField(max_length=50, blank=True, null=True)

    def __str__(self):
        return self.name

class Team(models.Model):
    team_name = models.CharField(max_length=100)
    teacher = models.ForeignKey(Teacher, on_delete=models.CASCADE)
    school_name = models.CharField(max_length=100, blank=True, null=True)
    created_date = models.DateTimeField(auto_now_add=True)
    points = models.IntegerField(default=0)

    def __str__(self):
        return self.team_name

class Product(models.Model):
    product_name = models.CharField(max_length=100)
    product_description = models.TextField(blank=True, null=True)
    quantity = models.IntegerField(default=0)
    product_price = models.DecimalField(max_digits=10, decimal_places=2)
    product_image = models.CharField(max_length=255, blank=True, null=True)
    status = models.CharField(max_length=20, default='active')
    upload_date = models.DateTimeField(auto_now_add=True)
    uploader = models.ForeignKey(User, on_delete=models.CASCADE)
    team = models.ForeignKey(Team, on_delete=models.CASCADE)

    def __str__(self):
        return self.product_name

class TeamMember(models.Model):
    team = models.ForeignKey(Team, on_delete=models.CASCADE)
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    joined_date = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('team', 'user')

    def __str__(self):
        return f'{self.user.name} - {self.team.team_name}'

class Order(models.Model):
    order_id = models.IntegerField()
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    product = models.ForeignKey(Product, on_delete=models.CASCADE)
    team = models.ForeignKey(Team, on_delete=models.CASCADE)
    quantity = models.IntegerField()
    order_date = models.DateTimeField(auto_now_add=True)
    status = models.CharField(max_length=20, default='pending')
    rating = models.IntegerField(blank=True, null=True)
    review = models.TextField(blank=True, null=True)
    rating_date = models.DateTimeField(blank=True, null=True)
    can_rate = models.BooleanField(default=True)

    def __str__(self):
        return f'Order {self.id} - {self.user.name} - {self.product.product_name}'
