import { computed } from 'vue';

// FDI World Dental Federation notation (two-digit system)
const FDI_TEETH = {
    // Upper right (quadrant 1)
    '18': 'Upper Right Third Molar',
    '17': 'Upper Right Second Molar',
    '16': 'Upper Right First Molar',
    '15': 'Upper Right Second Premolar',
    '14': 'Upper Right First Premolar',
    '13': 'Upper Right Canine',
    '12': 'Upper Right Lateral Incisor',
    '11': 'Upper Right Central Incisor',
    // Upper left (quadrant 2)
    '21': 'Upper Left Central Incisor',
    '22': 'Upper Left Lateral Incisor',
    '23': 'Upper Left Canine',
    '24': 'Upper Left First Premolar',
    '25': 'Upper Left Second Premolar',
    '26': 'Upper Left First Molar',
    '27': 'Upper Left Second Molar',
    '28': 'Upper Left Third Molar',
    // Lower left (quadrant 3)
    '38': 'Lower Left Third Molar',
    '37': 'Lower Left Second Molar',
    '36': 'Lower Left First Molar',
    '35': 'Lower Left Second Premolar',
    '34': 'Lower Left First Premolar',
    '33': 'Lower Left Canine',
    '32': 'Lower Left Lateral Incisor',
    '31': 'Lower Left Central Incisor',
    // Lower right (quadrant 4)
    '41': 'Lower Right Central Incisor',
    '42': 'Lower Right Lateral Incisor',
    '43': 'Lower Right Canine',
    '44': 'Lower Right First Premolar',
    '45': 'Lower Right Second Premolar',
    '46': 'Lower Right First Molar',
    '47': 'Lower Right Second Molar',
    '48': 'Lower Right Third Molar',
} as const;

const QUADRANTS = {
    upper_right: ['18', '17', '16', '15', '14', '13', '12', '11'],
    upper_left: ['21', '22', '23', '24', '25', '26', '27', '28'],
    lower_left: ['38', '37', '36', '35', '34', '33', '32', '31'],
    lower_right: ['41', '42', '43', '44', '45', '46', '47', '48'],
};

export function useToothNotation() {
    const allTeeth = computed(() => Object.keys(FDI_TEETH));

    const upperTeeth = computed(() => [
        ...QUADRANTS.upper_right,
        ...QUADRANTS.upper_left,
    ]);

    const lowerTeeth = computed(() => [
        ...QUADRANTS.lower_left,
        ...QUADRANTS.lower_right,
    ]);

    function getToothName(number: string): string {
        return FDI_TEETH[number as keyof typeof FDI_TEETH] || `Tooth ${number}`;
    }

    function getQuadrant(number: string): string {
        const q = parseInt(number.charAt(0));
        switch (q) {
            case 1: return 'Upper Right';
            case 2: return 'Upper Left';
            case 3: return 'Lower Left';
            case 4: return 'Lower Right';
            default: return 'Unknown';
        }
    }

    return {
        allTeeth,
        upperTeeth,
        lowerTeeth,
        quadrants: QUADRANTS,
        getToothName,
        getQuadrant,
    };
}
