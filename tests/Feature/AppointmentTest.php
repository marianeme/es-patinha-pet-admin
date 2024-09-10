<?php

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Pet;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'PermissionsSeeder']);
    Artisan::call('db:seed', ['--class' => 'RolesSeeder']);
});

test('list of appointments is displayed', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointments = Appointment::factory()->for($employee)->count(3)->create();

    $response = $this->actingAs($employee->user, 'web')
        ->get(route('comercial.index'));

    foreach ($appointments as $appointment) {
        $this->assertDatabaseHas('appointments', [
            'employee_id' => $employee->id,
            'customer_id' => $appointment->customer->id,
            'pet_id' => $appointment->pet->id,
            'service_id' => $appointment->service->id,
            'status' => $appointment->status,
            'start_time' => $appointment->start_time,
            'end_time' => $appointment->end_time,
        ]);
    }
    $response->assertStatus(200);
});

test('list of appointments is empty when no appointments have been created', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $this->actingAs($employee->user, 'web')
        ->get(route('comercial.index'));

    $this->assertDatabaseCount('appointments', 0);
});

test('employee can update existing appointment info', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'pet_id' => $appointment->pet->id,
        'customer_id' => $appointment->customer->id,
        'service_id' => $appointment->service->id,
        'status' => 'completed',
        'start_time' => '2024-08-30T03:30',
        'end_time' => '2024-08-30T04:15',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response
        ->assertStatus(302)
        ->assertRedirect(route('comercial.index'))
        ->assertSessionHas('success', 'Registro de atendimento atualizado com sucesso.');

    $this->assertDatabaseHas('appointments', $updateData + ['employee_id' => $employee->id]);
});

test('employee cannot update non-existing appointment info', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $pet = Pet::factory()->create();
    $customer = Customer::factory()->create();
    $service = Service::factory()->create();

    $response = $this->actingAs($employee->user, 'web')
        ->put('/appointments/33', [
            'pet_id' => $pet->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'status' => 'canceled',
            'start_time' => '2024-08-30T03:54',
            'end_time' => null,
        ]);

    $response
        ->assertStatus(404);
});

test('employee cannot update an appointment w/ invalid pet id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'pet_id' => '1',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response->assertInvalid(['pet_id' => 'O campo de pet deve ser preenchido por um id válido.']);
});

test('employee cannot update an appointment w/ invalid customer id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'customer_id' => '1',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response->assertInvalid(['customer_id' => 'O campo de cliente deve ser preenchido por um id válido.']);
});

test('employee cannot update an appointment w/ invalid service id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'service_id' => '1',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response->assertInvalid(['service_id' => 'O campo de serviço deve ser preenchido por um id válido.']);
});

test('employee cannot update an appointment w/ invalid status', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'status' => 'postponed',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response->assertInvalid(['status' => 'O status do atendimento deve ser "pendente", "concluído" ou "cancelado".']);
});

test('employee cannot update an appointment w/ invalid start time', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'start_time' => '2000 01',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response->assertInvalid(['start_time' => 'O horário para o atendimento deve ser uma data-hora válida.']);
});

test('employee cannot update an appointment w/ invalid end time', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'end_time' => '21',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response->assertInvalid(['end_time' => 'O horário de conclusão do atendimento deve ser uma data-hora válida.']);
});


test('employee cannot update an appointment without end time when status is completed', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->for($employee)->create();

    $updateData = [
        'pet_id' => $appointment->pet->id,
        'customer_id' => $appointment->customer->id,
        'service_id' => $appointment->service->id,
        'status' => 'completed',
        'start_time' => '2024-08-30T03:30',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response->assertInvalid(['end_time' => 'O campo horário de conclusão do atendimento é obrigatório quando o status é "concluído".']);
});

test('employee can destroy existing appointment', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $appointment = Appointment::factory()->create();

    $response = $this->actingAs($employee->user, 'web')
        ->delete('/appointments/'.$appointment->id);

    $createdAppointment = appointment::find($appointment->id);

    $response
        ->assertStatus(302)
        ->assertRedirect(route('comercial.index'))
        ->assertSessionHas('success', 'Registro de atendimento removido com sucesso.');

    $this->assertEquals(null, $createdAppointment);
    $this->assertDatabaseMissing('appointments', [
        'id' => $appointment->id,
    ]);
});

test('employee cannot destroy non-existing appointment', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $response = $this->actingAs($employee->user, 'web')
        ->delete('/appointments/33');
    $response->assertStatus(404);
});

test('employee can store a new appointment', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();
    $customer = Customer::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'start_time' => '2024-08-30T03:54',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response
        ->assertStatus(302)
        ->assertRedirect(route('comercial.index'))
        ->assertSessionHas('success', 'Registro de atendimento criado com sucesso.');

    // o id do funcinário que criou o agendamento é o 'employee_id' associado ao agendamento:
    $this->assertDatabaseHas('appointments', $data + ['employee_id' => $employee->id]);
});

test('employee cannot store an appointment without pet id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $service = Service::factory()->create();
    $customer = Customer::factory()->create();

    $data = [
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['pet_id' => 'O campo de pet é obrigatório.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment w/ invalid pet id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $service = Service::factory()->create();
    $customer = Customer::factory()->create();

    $data = [
        'pet_id' => '2',
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['pet_id' => 'O campo de pet deve ser preenchido por um id válido.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment without customer id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['customer_id' => 'O campo de cliente é obrigatório.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment w/ invalid customer id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => '3',
        'service_id' => $service->id,
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['customer_id' => 'O campo de cliente deve ser preenchido por um id válido.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment without service id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $customer = User::factory()->hasCustomer()->create()->customer;
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['service_id' => 'O campo de serviço é obrigatório.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment w/ invalid service id', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $customer = User::factory()->hasCustomer()->create()->customer;
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'service_id' => '2',
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['service_id' => 'O campo de serviço deve ser preenchido por um id válido.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment w/ invalid status', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $customer = User::factory()->hasCustomer()->create()->customer;
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'postponed',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['status' => 'O status do atendimento deve ser "pendente", "concluído" ou "cancelado".']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment without start time', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $customer = User::factory()->hasCustomer()->create()->customer;
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['start_time' => 'O campo de horário do atendimento é obrigatório.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment without end time when status is completed', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $customer = User::factory()->hasCustomer()->create()->customer;
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'completed',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['end_time' => 'O campo horário de conclusão do atendimento é obrigatório quando o status é "concluído".']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment w/ invalid start time', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $customer = User::factory()->hasCustomer()->create()->customer;
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'start_time' => '2024 00',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['start_time' => 'O horário para o atendimento deve ser uma data-hora válida.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee cannot store an appointment w/ invalid end time', function () {
    $employee = Employee::factory()->create();
    $employee->assignRole('admin');
    $customer = User::factory()->hasCustomer()->create()->customer;
    $service = Service::factory()->create();
    $pet = Pet::factory()->create();

    $data = [
        'pet_id' => $pet->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => '2024 00',
    ];

    $response = $this
        ->actingAs($employee->user, 'web')
        ->post(route('appointments.store'), $data);

    $response->assertInvalid(['end_time' => 'O horário de conclusão do atendimento deve ser uma data-hora válida.']);

    $this->assertDatabaseMissing('appointments', [
        'employee_id' => $employee->id,
    ]);
});

test('employee can update appointment of another employee', function () {
    $employee1 = Employee::factory()->create();
    $appointment = Appointment::factory()->for($employee1)->create();

    $employee2 = Employee::factory()->create();
    $employee2->assignRole('admin');

    $updateData = [
        'pet_id' => $appointment->pet->id,
        'customer_id' => $appointment->customer->id,
        'service_id' => $appointment->service->id,
        'status' => 'pending',
        'start_time' => '2024-08-30T03:55',
        'end_time' => null,
    ];

    $response = $this
        ->actingAs($employee2->user, 'web')
        ->put(route('appointments.update', compact('appointment')), $updateData);

    $response
        ->assertStatus(302)
        ->assertRedirect(route('comercial.index'))
        ->assertSessionHas('success', 'Registro de atendimento atualizado com sucesso.');

    // O employee_id associado ao agendamento continua sendo o id do funcionário que criou o agendamento:
    $this->assertDatabaseHas('appointments', $updateData + ['employee_id' => $employee1->id]);
});
