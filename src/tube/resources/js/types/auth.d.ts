export type Auth = {
    user: {
        id: number;
        name: string;
        is_active: boolean;
    }
    | null;
};