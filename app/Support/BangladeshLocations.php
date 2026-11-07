<?php

namespace App\Support;

class BangladeshLocations
{
    /**
     * Complete administrative map of Bangladesh: Division => [ District => [ Upazilas... ] ]
     *
     * @return array<string, array<string, array<int, string>>>
     */
    public static function all(): array
    {
        return [
            'Dhaka' => [
                'Dhaka' => [
                    'Adabor', 'Badda', 'Bangshal', 'Bimanbandar', 'Cantonment', 'Chakbazar', 'Dakshinkhan', 'Darus Salam',
                    'Demra', 'Dhamrai', 'Dhanmondi', 'Dohar', 'Gendaria', 'Gulshan', 'Hazaribagh', 'Jatrabari',
                    'Kadamtali', 'Kafrul', 'Kalabagan', 'Kamrangirchar', 'Keraniganj', 'Khilgaon', 'Khilkhet', 'Kotwali',
                    'Lalbagh', 'Mirpur', 'Mohammadpur', 'Motijheel', 'Nawabganj', 'New Market', 'Pallabi', 'Paltan',
                    'Ramna', 'Rampura', 'Sabujbagh', 'Savar', 'Shah Ali', 'Shahbagh', 'Sher-e-Bangla Nagar', 'Shyampur',
                    'Sutrapur', 'Tejgaon', 'Tejgaon Industrial Area', 'Turag', 'Uttara', 'Uttar Khan', 'Vatara', 'Wari',
                ],
                'Gazipur' => [
                    'Gazipur Sadar', 'Kaliakair', 'Kaliganj', 'Kapasia', 'Sreepur', 'Tongi',
                ],
                'Narayanganj' => [
                    'Narayanganj Sadar', 'Araihazar', 'Bandar', 'Rupganj', 'Sonargaon',
                ],
                'Tangail' => [
                    'Tangail Sadar', 'Basail', 'Bhuapur', 'Delduar', 'Dhanbari', 'Ghatail', 'Gopalpur', 'Kalihati', 'Madhupur', 'Mirzapur', 'Nagarpur', 'Sakhipur',
                ],
                'Kishoreganj' => [
                    'Kishoreganj Sadar', 'Austagram', 'Bajitpur', 'Bhairab', 'Hossainpur', 'Itna', 'Karimganj', 'Katiadi', 'Kuliarchar', 'Mithamain', 'Nikli', 'Pakundia', 'Tarail',
                ],
                'Manikganj' => [
                    'Manikganj Sadar', 'Daulatpur', 'Ghiror', 'Harirampur', 'Saturia', 'Shivalaya', 'Singair',
                ],
                'Munshiganj' => [
                    'Munshiganj Sadar', 'Gazaria', 'Lohajang', 'Sirajdikhan', 'Sreenagar', 'Tongibari',
                ],
                'Narsingdi' => [
                    'Narsingdi Sadar', 'Belabo', 'Monohardi', 'Palash', 'Raipura', 'Shibpur',
                ],
                'Faridpur' => [
                    'Faridpur Sadar', 'Alfadanga', 'Bhanga', 'Boalmari', 'Charbhadrasan', 'Madhukhali', 'Nagarkanda', 'Sadarpur', 'Saltha',
                ],
                'Gopalganj' => [
                    'Gopalganj Sadar', 'Kashiani', 'Kotalipara', 'Muksudpur', 'Tungipara',
                ],
                'Madaripur' => [
                    'Madaripur Sadar', 'Kalkini', 'Rajoir', 'Shibchar', 'Dasar',
                ],
                'Rajbari' => [
                    'Rajbari Sadar', 'Baliakandi', 'Goalandaghat', 'Pangsha', 'Kalukhali',
                ],
                'Shariatpur' => [
                    'Shariatpur Sadar', 'Bhedarganj', 'Damudya', 'Gosairhat', 'Naria', 'Zajira',
                ],
            ],
            'Chattogram' => [
                'Chattogram' => [
                    'Anwara', 'Banshkhali', 'Boalkhali', 'Chandanaish', 'Fatikchhari', 'Hathazari', 'Karnaphuli', 'Lohagara',
                    'Mirsharai', 'Patiya', 'Rangunia', 'Raozan', 'Sandwip', 'Satkania', 'Sitakunda', 'Kotwali', 'Panchlaish',
                    'Double Mooring', 'Halishahar', 'Pahartali', 'Chandgaon', 'Khulshi', 'Bakalia', 'Bayazid', 'Patenga',
                ],
                'Cox\'s Bazar' => [
                    'Cox\'s Bazar Sadar', 'Chakaria', 'Kutubdia', 'Maheshkhali', 'Pekua', 'Ramu', 'Teknaf', 'Ukhia', 'Eidgaon',
                ],
                'Cumilla' => [
                    'Cumilla Adarsha Sadar', 'Cumilla Sadar Dakshin', 'Barura', 'Brahmanpara', 'Burichang', 'Chandina', 'Chauddagram',
                    'Daudkandi', 'Debidwar', 'Homna', 'Laksam', 'Lalmai', 'Meghna', 'Monohargonj', 'Muradnagar', 'Nangalkot', 'Titas',
                ],
                'Feni' => [
                    'Feni Sadar', 'Chhagalnaiya', 'Daganbhuiyan', 'Parshuram', 'Fulgazi', 'Sonagazi',
                ],
                'Brahmanbaria' => [
                    'Brahmanbaria Sadar', 'Ashuganj', 'Bancharampur', 'Bijoynagar', 'Kasba', 'Nabinagar', 'Nasirnagar', 'Sarail', 'Akhaura',
                ],
                'Noakhali' => [
                    'Noakhali Sadar', 'Begumganj', 'Chatkhil', 'Companiganj', 'Hatiya', 'Senbagh', 'Sonaimuri', 'Subarnachar', 'Kabirhat',
                ],
                'Chandpur' => [
                    'Chandpur Sadar', 'Faridganj', 'Haimchar', 'Haziganj', 'Kachua', 'Matlab Dakshin', 'Matlab Uttar', 'Shahrasti',
                ],
                'Lakshmipur' => [
                    'Lakshmipur Sadar', 'Raipur', 'Ramganj', 'Ramgati', 'Kamalnagar',
                ],
                'Khagrachhari' => [
                    'Khagrachhari Sadar', 'Dighinala', 'Lakshmichhari', 'Mahalchhari', 'Manikchhari', 'Matiranga', 'Panchhari', 'Ramgarh', 'Guimara',
                ],
                'Rangamati' => [
                    'Rangamati Sadar', 'Bagaichhari', 'Barkal', 'Belaichhari', 'Juraichhari', 'Kaptai', 'Kawkhali', 'Langadu', 'Naniarchar', 'Rajasthali',
                ],
                'Bandarban' => [
                    'Bandarban Sadar', 'Ali Kadam', 'Lama', 'Naikhongchhari', 'Rowangchhari', 'Ruma', 'Thanchi',
                ],
            ],
            'Rajshahi' => [
                'Rajshahi' => [
                    'Boalia', 'Motihar', 'Rajpara', 'Shah Makhdum', 'Bagha', 'Bagmara', 'Charghat', 'Durgapur', 'Godagari', 'Mohanpur', 'Paba', 'Puthia', 'Tanore',
                ],
                'Bogura' => [
                    'Bogura Sadar', 'Adamdighi', 'Dhunat', 'Dhupchanchia', 'Gabtali', 'Kahaloo', 'Nandigram', 'Sariakandi', 'Shajahanpur', 'Sherpur', 'Shibganj', 'Sonatala',
                ],
                'Joypurhat' => [
                    'Joypurhat Sadar', 'Akkelpur', 'Kalai', 'Khetlal', 'Panchbibi',
                ],
                'Naogaon' => [
                    'Naogaon Sadar', 'Atrai', 'Badalgachhi', 'Dhamoirhat', 'Manda', 'Mohadevpur', 'Niamatpur', 'Patnitala', 'Porsha', 'Raninagar', 'Sapahar',
                ],
                'Natore' => [
                    'Natore Sadar', 'Bagatipara', 'Baraigram', 'Gurudaspur', 'Lalpur', 'Singra', 'Naldanga',
                ],
                'Chapainawabganj' => [
                    'Chapainawabganj Sadar', 'Bholahat', 'Gomastapur', 'Nachole', 'Shibganj',
                ],
                'Pabna' => [
                    'Pabna Sadar', 'Atgharia', 'Bera', 'Bhangura', 'Chatmohar', 'Faridpur', 'Ishwardi', 'Santhia', 'Sujanagar',
                ],
                'Sirajganj' => [
                    'Sirajganj Sadar', 'Belkuchi', 'Chauhali', 'Kamarkhanda', 'Kazipur', 'Raiganj', 'Shahjadpur', 'Tarash', 'Ullahpara',
                ],
            ],
            'Khulna' => [
                'Khulna' => [
                    'Khulna Sadar', 'Daulatpur', 'Khalishpur', 'Khan Jahan Ali', 'Sonadanga', 'Batiaghata', 'Dacope', 'Dumuria', 'Dighalia', 'Koyra', 'Paikgachha', 'Phultala', 'Rupsha', 'Terokhada',
                ],
                'Bagerhat' => [
                    'Bagerhat Sadar', 'Chitalmari', 'Fakirhat', 'Kachua', 'Mollahat', 'Mongla', 'Morrelganj', 'Rampal', 'Sarankhola',
                ],
                'Chuadanga' => [
                    'Chuadanga Sadar', 'Alamdanga', 'Damurhuda', 'Jibannagar',
                ],
                'Jashore' => [
                    'Jashore Sadar', 'Abhaynagar', 'Bagherpara', 'Chaugachha', 'Jhikargachha', 'Keshabpur', 'Manirampur', 'Sharsha',
                ],
                'Jhenaidah' => [
                    'Jhenaidah Sadar', 'Harinakunda', 'Kaliganj', 'Kotchandpur', 'Maheshpur', 'Shailkupa',
                ],
                'Kushtia' => [
                    'Kushtia Sadar', 'Bheramara', 'Daulatpur', 'Khoksa', 'Kumarkhali', 'Mirpur',
                ],
                'Magura' => [
                    'Magura Sadar', 'Mohammadpur', 'Shalikha', 'Sreepur',
                ],
                'Meherpur' => [
                    'Meherpur Sadar', 'Gangni', 'Mujibnagar',
                ],
                'Narail' => [
                    'Narail Sadar', 'Kalia', 'Lohagara',
                ],
                'Satkhira' => [
                    'Satkhira Sadar', 'Assasuni', 'Debhata', 'Kalaroa', 'Kaliganj', 'Shyamnagar', 'Tala',
                ],
            ],
            'Barishal' => [
                'Barishal' => [
                    'Barishal Sadar', 'Agailjhara', 'Babuganj', 'Bakerganj', 'Banaripara', 'Gaurnadi', 'Hizla', 'Mehendiganj', 'Muladi', 'Wazirpur',
                ],
                'Barguna' => [
                    'Barguna Sadar', 'Amtali', 'Bamna', 'Betagi', 'Patharghata', 'Taltali',
                ],
                'Bhola' => [
                    'Bhola Sadar', 'Burhanuddin', 'Char Fasson', 'Daulatkhan', 'Lalmohan', 'Manpura', 'Tazumuddin',
                ],
                'Jhalokathi' => [
                    'Jhalokathi Sadar', 'Kathalia', 'Nalchity', 'Rajapur',
                ],
                'Patuakhali' => [
                    'Patuakhali Sadar', 'Bauphal', 'Dashmina', 'Galachipa', 'Kalapara', 'Mirzaganj', 'Rangabali', 'Dumki',
                ],
                'Pirojpur' => [
                    'Pirojpur Sadar', 'Bhandaria', 'Kawkhali', 'Mathbaria', 'Nazirpur', 'Nesarabad (Swarupkati)', 'Zianagar (Indurkani)',
                ],
            ],
            'Sylhet' => [
                'Sylhet' => [
                    'Sylhet Sadar', 'Balaganj', 'Beanibazar', 'Bishwanath', 'Companiganj', 'Fenchuganj', 'Golapganj', 'Gowainghat', 'Jaintiapur', 'Kanaighat', 'Osmani Nagar', 'South Surma', 'Zakiganj',
                ],
                'Habiganj' => [
                    'Habiganj Sadar', 'Ajmiriganj', 'Bahubal', 'Baniachong', 'Chunarughat', 'Lakhai', 'Madhabpur', 'Nabiganj', 'Sayestaganj',
                ],
                'Moulvibazar' => [
                    'Moulvibazar Sadar', 'Barlekha', 'Juri', 'Kamalganj', 'Kulaura', 'Rajnagar', 'Sreemangal',
                ],
                'Sunamganj' => [
                    'Sunamganj Sadar', 'Bishwamvarpur', 'Chhatak', 'Derai', 'Dharampasha', 'Dowarabazar', 'Jagannathpur', 'Jamalganj', 'Shantiganj', 'Sullah', 'Tahirpur', 'Madhyanagar',
                ],
            ],
            'Rangpur' => [
                'Rangpur' => [
                    'Rangpur Sadar', 'Badarganj', 'Gangachhara', 'Kaunia', 'Mithapukur', 'Pirgachha', 'Pirganj', 'Taraganj',
                ],
                'Dinajpur' => [
                    'Dinajpur Sadar', 'Birampur', 'Birganj', 'Biral', 'Bochaganj', 'Chirirbandar', 'Fulbari', 'Ghoraghat', 'Hakimpur', 'Kaharole', 'Khansama', 'Nawabganj', 'Parbatipur',
                ],
                'Gaibandha' => [
                    'Gaibandha Sadar', 'Fulchhari', 'Gobindaganj', 'Palashbari', 'Sadullapur', 'Saghata', 'Sundarganj',
                ],
                'Kurigram' => [
                    'Kurigram Sadar', 'Bhurungamari', 'Char Rajibpur', 'Chilmari', 'Nageshwari', 'Phulbari', 'Rajarhat', 'Raomari', 'Ulipur',
                ],
                'Lalmonirhat' => [
                    'Lalmonirhat Sadar', 'Aditmari', 'Hatibandha', 'Kaliganj', 'Patgram',
                ],
                'Nilphamari' => [
                    'Nilphamari Sadar', 'Dimla', 'Domar', 'Jaldhaka', 'Kishoreganj', 'Syedpur',
                ],
                'Panchagarh' => [
                    'Panchagarh Sadar', 'Atwari', 'Boda', 'Debiganj', 'Tetulia',
                ],
                'Thakurgaon' => [
                    'Thakurgaon Sadar', 'Baliadangi', 'Haripur', 'Pirganj', 'Ranisankhail',
                ],
            ],
            'Mymensingh' => [
                'Mymensingh' => [
                    'Mymensingh Sadar', 'Bhaluka', 'Dhobaura', 'Fulbaria', 'Gafargaon', 'Gauripur', 'Haluaghat', 'Ishwarganj', 'Muktagachha', 'Nandail', 'Phulpur', 'Tara Khanda', 'Trishal',
                ],
                'Jamalpur' => [
                    'Jamalpur Sadar', 'Baksiganj', 'Dewanganj', 'Islampur', 'Madarganj', 'Melandaha', 'Sarishabari',
                ],
                'Netrokona' => [
                    'Netrokona Sadar', 'Atpara', 'Barhatta', 'Durgapur', 'Kalmakanda', 'Kendua', 'Madan', 'Mohanganj', 'Purbadhala', 'Khaliajuri',
                ],
                'Sherpur' => [
                    'Sherpur Sadar', 'Jhenaigati', 'Nakla', 'Nalitabari', 'Sreebardi',
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function divisions(): array
    {
        return array_keys(static::all());
    }

    /**
     * Get districts, optionally filtered by division.
     *
     * @return array<string, string> district_name => division_name
     */
    public static function districtsMap(): array
    {
        $map = [];
        foreach (static::all() as $division => $districts) {
            foreach (array_keys($districts) as $district) {
                $map[$district] = $division;
            }
        }
        ksort($map);

        return $map;
    }

    /**
     * @return array<string, array<int, string>> district_name => [upazilas...]
     */
    public static function upazilasMap(): array
    {
        $map = [];
        foreach (static::all() as $districts) {
            foreach ($districts as $district => $upazilas) {
                sort($upazilas);
                $map[$district] = $upazilas;
            }
        }
        ksort($map);

        return $map;
    }
}
