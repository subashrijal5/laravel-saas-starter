import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AvailableRole } from '@/types';

export default function MemberRoleSelect({
    roles,
    value,
    onValueChange,
    disabled = false,
}: {
    roles: AvailableRole[];
    value: string;
    onValueChange: (value: string) => void;
    disabled?: boolean;
}) {
    return (
        <Select value={value} onValueChange={onValueChange} disabled={disabled}>
            <SelectTrigger className="w-[140px]">
                <SelectValue placeholder="Select role" />
            </SelectTrigger>
            <SelectContent>
                {roles
                    .filter((role) => role.key !== 'owner')
                    .map((role) => (
                        <SelectItem key={role.key} value={role.key}>
                            {role.label}
                        </SelectItem>
                    ))}
            </SelectContent>
        </Select>
    );
}
